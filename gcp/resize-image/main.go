/*
 * Copyright 2022 Google LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

package main

import (
	"cloud.google.com/go/storage"
	"context"
	"encoding/json"
	"errors"
	"fmt"
	cloudevent "github.com/cloudevents/sdk-go/v2"
	"github.com/googleapis/google-cloudevents-go/cloud/storagedata"
	"google.golang.org/protobuf/encoding/protojson"
	"io"
	"log"
	"net/http"
	"os"
	"sort"
	"strconv"
	"strings"
	"time"

	"gopkg.in/gographics/imagick.v3/imagick"
)

func main() {
	log.Print("starting server...")
	http.HandleFunc("/", handleResize)
	http.HandleFunc("/size", handleImageSize)
	http.HandleFunc("/proactive_resize", handleProactiveResize)

	// Determine port for HTTP service.
	port := os.Getenv("PORT")
	if port == "" {
		port = "8080"
		log.Printf("defaulting to port %s", port)
	}

	// Start imagick
	imagick.Initialize()
	defer imagick.Terminate()

	// Start HTTP server.
	log.Printf("listening on port %s", port)
	if err := http.ListenAndServe(":"+port, nil); err != nil {
		log.Fatal(err)
	}
}

func sanitize(s string) string {
	return strings.Replace(strings.Replace(s, "\n", "", -1), "\r", "", -1)
}

func resizeToHeight(mw *imagick.MagickWand, nh uint, filter imagick.FilterType) error {
	if err := mw.SetImageCompression(imagick.COMPRESSION_JPEG); err != nil {
		return err
	}
	if err := mw.SetImageCompressionQuality(90); err != nil {
		return err
	}
	if err := mw.SetFormat("jpg"); err != nil {
		return err
	}
	ow := mw.GetImageWidth()
	oh := mw.GetImageHeight()
	if oh < nh {
		nh = oh
	}
	nw := ow * nh / oh
	return mw.ResizeImage(nw, nh, filter)
}

func handleResize(w http.ResponseWriter, r *http.Request) {
	log.Printf("Got a resize-image request, %v", time.Now())
	mw := imagick.NewMagickWand()
	defer mw.Destroy()

	var h1 string
	rf := ""
	if r.Method == "POST" {
		h1 = r.PostFormValue("height")
		rf = strings.ToLower(r.PostFormValue("filter"))
	} else {
		heights := r.URL.Query()["height"]
		if len(heights) != 1 {
			http.Error(w, fmt.Sprintf("Expected 1 target height, got %v", len(heights)), http.StatusBadRequest)
			log.Printf("len(heights): %v", len(heights))
			return
		}
		h1 = heights[0]
		filters := r.URL.Query()["filter"]
		if len(filters) > 1 {
			http.Error(w, fmt.Sprintf("Expected at most 1 filter, got %v", len(filters)), http.StatusBadRequest)
			log.Printf("len(filters): %v", len(filters))
			return
		}
		if len(filters) == 1 {
			rf = filters[0]
		}
	}
	h2, err := strconv.Atoi(h1)
	if err != nil {
		http.Error(w, fmt.Sprintf("Error parsing supplied height %v", h1), http.StatusBadRequest)
		log.Printf("Error parsing supplied height %v", sanitize(h1))
		return
	}
	nh := uint(h2)

	filter, err := parseFilter(rf)
	if err != nil {
		http.Error(w, fmt.Sprintf("Unrecognized filter %v", rf), http.StatusBadRequest)
		log.Printf("Unrecognized filter %v", sanitize(rf))
		return
	}

	var b []byte
	if r.Method == "POST" {
		b, err = readFormFile(r)
		if err != nil {
			http.Error(w, err.Error(), http.StatusInternalServerError)
			log.Printf("Error handling POST to resize: %v", err)
			return
		}
	} else {
		ids := r.URL.Query()["object"]
		buckets := r.URL.Query()["bucket"]
		if len(ids) != 1 {
			msg := fmt.Sprintf("Expected 1 object, got %v", len(ids))
			http.Error(w, msg, http.StatusBadRequest)
			log.Printf("len(ids): %v", len(ids))
			return
		}
		if len(buckets) != 1 {
			msg := fmt.Sprintf("Expected 1 bucket, got %v", len(ids))
			http.Error(w, msg, http.StatusBadRequest)
			log.Printf("len(buckets): %v", len(buckets))
			return
		}
		id := ids[0]
		bucket := buckets[0]
		b, err = readFromBucket(id, bucket)
		if err != nil {
			msg := fmt.Sprintf("Error reading %v:stored/%v: %v", bucket, id, err)
			http.Error(w, msg, http.StatusInternalServerError)
			log.Printf(msg)
			return
		}
	}

	if err := mw.ReadImageBlob(b); err != nil {
		http.Error(w, "Error reading image", http.StatusBadRequest)
		log.Printf("Error reading image: %v", err)
		return
	}

	if err := resizeToHeight(mw, nh, filter); err != nil {
		http.Error(w, "Error resizing image", http.StatusInternalServerError)
		log.Printf("Error resizing image: %v", err)
		return
	}

	out := mw.GetImageBlob()
	log.Printf("Response image is %v bytes", len(out))

	w.Header().Set("Content-Type", "image/jpeg")
	if _, err := w.Write(out); err != nil {
		http.Error(w, "Error writing response", http.StatusInternalServerError)
		log.Printf("Error writing response: %v", err)
	}
}

func handleImageSize(w http.ResponseWriter, r *http.Request) {
	log.Printf("Got an image-size request, %v", time.Now())
	mw := imagick.NewMagickWand()
	defer mw.Destroy()

	var b []byte
	var err error
	if r.Method == "POST" {
		b, err = readFormFile(r)
		if err != nil {
			http.Error(w, err.Error(), http.StatusInternalServerError)
			log.Printf("Error handling POST to size: %v", err)
			return
		}
	} else {
		ids := r.URL.Query()["object"]
		buckets := r.URL.Query()["bucket"]
		if len(ids) != 1 {
			msg := fmt.Sprintf("Expected 1 object, got %v", len(ids))
			http.Error(w, msg, http.StatusBadRequest)
			log.Printf("len(ids): %v", len(ids))
			return
		}
		if len(buckets) != 1 {
			msg := fmt.Sprintf("Expected 1 bucket, got %v", len(ids))
			http.Error(w, msg, http.StatusBadRequest)
			log.Printf("len(buckets): %v", len(buckets))
			return
		}
		id := ids[0]
		bucket := buckets[0]
		b, err = readFromBucket(id, bucket)
		if err != nil {
			msg := fmt.Sprintf("Error reading %v:stored/%v: %v", bucket, id, err)
			http.Error(w, msg, http.StatusInternalServerError)
			log.Printf(msg)
			return
		}
	}

	if err := mw.ReadImageBlob(b); err != nil {
		http.Error(w, "Error reading image", http.StatusBadRequest)
		log.Printf("Error reading image: %v", err)
		return
	}

	ow := mw.GetImageWidth()
	oh := mw.GetImageHeight()
	log.Printf("Image is %vx%v", ow, oh)

	w.Header().Set("Content-Type", "application/json")
	if err := json.NewEncoder(w).Encode(map[string]uint{"width": ow, "height": oh}); err != nil {
		http.Error(w, "Error writing response", http.StatusInternalServerError)
		log.Printf("Error writing response: %v", err)
	}
}

func handleProactiveResize(w http.ResponseWriter, r *http.Request) {
	log.Printf("Got a proactive-resize request, %v", time.Now())
	mw := imagick.NewMagickWand()
	defer mw.Destroy()

	rf := ""
	filters := r.URL.Query()["filter"]
	if len(filters) > 1 {
		http.Error(w, fmt.Sprintf("Expected at most 1 filter, got %v", len(filters)), http.StatusBadRequest)
		log.Printf("len(filters): %v", len(filters))
		return
	}
	if len(filters) == 1 {
		rf = filters[0]
	}
	filter, err := parseFilter(rf)
	if err != nil {
		http.Error(w, fmt.Sprintf("Unrecognized filter %v", rf), http.StatusBadRequest)
		log.Printf("Unrecognized filter %v", sanitize(rf))
		return
	}

	var id string
	var bucket string
	if r.Method == "POST" {
		ce, err := cloudevent.NewEventFromHTTPRequest(r)
		if err != nil {
			log.Printf("cloudevent.NewEventFromHTTPRequest: %v", err)
			http.Error(w, "Failed to create CloudEvent from request.", http.StatusBadRequest)
			return
		}
		var so storagedata.StorageObjectData
		err = protojson.Unmarshal(ce.Data(), &so)
		if err != nil {
			log.Printf("failed to unmarshal: %v", err)
			http.Error(w, "Bad Request: expected Cloud Storage event", http.StatusBadRequest)
			return
		}
		bucket = so.GetBucket()
		name := so.GetName()
		if !strings.HasPrefix(name, "stored/") {
			log.Printf("ignoring non-stored/ object %v", name)
			return
		}
		id = name[7:]

		// It seems like the trigger keeps firing multiple times after upload for some reason.
		// To dedupe these requests, check if cached versions already exist.
		// Checking for size 64 as it is the most likely to exist for any given image after
		// a proactive-resize request completes.
		storageClient, err := storage.NewClient(context.Background())
		if err != nil {
			log.Printf("failed initialize storageClient: %v", err)
			http.Error(w, "Failed to check for existing cached version", http.StatusInternalServerError)
			return
		}
		defer func(storageClient *storage.Client) {
			err := storageClient.Close()
			if err != nil {
				log.Printf("error closing storage client: %v", err)
			}
		}(storageClient)
		_, err = storageClient.Bucket(bucket).Object(fmt.Sprintf("cache/%s_64.jpg", id)).Attrs(context.Background())
		if errors.Is(err, storage.ErrObjectNotExist) {
			log.Printf("cache/%s_64.jpg does not exist; continuing", id)
		} else if err != nil {
			log.Printf("error getting attrs for cache/%s_64.jpg: %v", id, err)
			http.Error(w, "Failed to check for existing cached version", http.StatusInternalServerError)
			return
		} else {
			log.Printf("found cached version %s_64.jpg; exiting", id)
			return
		}
	} else {
		ids := r.URL.Query()["object"]
		buckets := r.URL.Query()["bucket"]
		if len(ids) != 1 {
			msg := fmt.Sprintf("Expected 1 object, got %v", len(ids))
			http.Error(w, msg, http.StatusBadRequest)
			log.Printf("len(ids): %v", len(ids))
			return
		}
		if len(buckets) != 1 {
			msg := fmt.Sprintf("Expected 1 bucket, got %v", len(ids))
			http.Error(w, msg, http.StatusBadRequest)
			log.Printf("len(buckets): %v", len(buckets))
			return
		}
		id = ids[0]
		bucket = buckets[0]
	}

	b, err := readFromBucket(id, bucket)
	if err != nil {
		msg := fmt.Sprintf("Error reading %v:stored/%v: %v", bucket, id, err)
		http.Error(w, msg, http.StatusInternalServerError)
		log.Printf(msg)
		return
	}

	if err := mw.ReadImageBlob(b); err != nil {
		http.Error(w, "Error reading image", http.StatusBadRequest)
		log.Printf("Error reading image: %v", err)
		return
	}

	storageClient, err := storage.NewClient(context.Background())
	if err != nil {
		http.Error(w, "Error initializing storage client", http.StatusInternalServerError)
		log.Printf("storage.NewClient: %v", err)
		return
	}
	defer func(storageClient *storage.Client) {
		err := storageClient.Close()
		if err != nil {
			log.Printf("error closing storage client: %v", err)
		}
	}(storageClient)

	// Need all heights used in the frontend here.
	// TODO [#854]: proactive-cache only the relevant heights for the specific type of image
	kh := []int{64, 192, 300, 480, 600}

	// behold: an empty set. i hate it
	hs := map[int]struct{}{}

	// Mirror implementation from assets.php for scaled sizes
	oh := int(mw.GetImageHeight())
	for _, h := range kh {
		cs := 1.
		for nh := h; nh < oh; nh = int(cs * float64(h)) {
			hs[nh] = struct{}{}
			if cs < 2 {
				cs += 0.5
			} else if cs < 4 {
				cs += 1
			} else {
				cs *= 2
			}
		}
	}

	var heights []int
	for h := range hs {
		heights = append(heights, h)
	}
	sort.Sort(sort.Reverse(sort.IntSlice(heights)))

	// TODO [#855]: Start by copying the original object to all new locations, so they are available sooner.
	// Not sure if this will actually matter in real world scenarios.
	// See: https://cloud.google.com/storage/docs/copying-renaming-moving-objects#client-libraries

	// TODO [#856]: consider cloning the mw for each resize to avoid generation loss
	for _, h := range heights {
		if err := resizeToHeight(mw, uint(h), filter); err != nil {
			http.Error(w, fmt.Sprintf("Error resizing image to height %v", h), http.StatusInternalServerError)
			log.Printf("Error resizing image to height %v: %v", h, err)
			return
		}
		out := mw.GetImageBlob()
		name := fmt.Sprintf("cache/%v_%v.jpg", id, h)
		log.Printf("%v:%v will be %v bytes", bucket, name, len(out))
		obj := storageClient.Bucket(bucket).Object(name)
		writer := obj.NewWriter(context.Background())
		n, err := writer.Write(out)
		if err != nil {
			http.Error(w, fmt.Sprintf("Error writing output file %v", name), http.StatusInternalServerError)
			log.Printf("error writing %v: %v", name, err)
			return
		}
		if n != len(out) {
			http.Error(w, fmt.Sprintf("Error writing output file %v", name), http.StatusInternalServerError)
			log.Printf("only wrote %v bytes to %v", n, name)
			return
		}
		if err = writer.Close(); err != nil {
			http.Error(w, fmt.Sprintf("Error closing writer for %v", name), http.StatusInternalServerError)
			log.Printf("error closing writer %v: %v", name, err)
			return
		}
	}
}

func readFormFile(r *http.Request) ([]byte, error) {
	if err := r.ParseMultipartForm(20 << 20); err != nil {
		return nil, err
	}
	defer func() {
		if err := r.MultipartForm.RemoveAll(); err != nil {
			log.Printf("Error removing temporary files: %v", err)
		}
	}()

	f, fh, err := r.FormFile("image")
	if err != nil {
		return nil, err
	}

	s := int(fh.Size)
	log.Printf("Payload is %v bytes", s)
	b := make([]byte, s)
	n, err := f.Read(b)
	if err != nil {
		return nil, err
	}
	if n != s {
		return nil, errors.New(fmt.Sprintf("Read %v bytes, expected %v", n, s))
	}

	return b, nil
}

func readFromBucket(id string, bucket string) ([]byte, error) {
	log.Printf("Reading image %v from storage", id)
	storageClient, err := storage.NewClient(context.Background())
	if err != nil {
		return nil, err
	}
	defer func(storageClient *storage.Client) {
		err := storageClient.Close()
		if err != nil {
			log.Printf("error closing storage client: %v", err)
		}
	}(storageClient)
	name := fmt.Sprintf("stored/%s", id)
	obj := storageClient.Bucket(bucket).Object(name)
	reader, err := obj.NewReader(context.Background())
	if err != nil {
		return nil, err
	}
	defer func(reader *storage.Reader) {
		err := reader.Close()
		if err != nil {
			log.Printf("Error closing reader: %v", err)
		}
	}(reader)
	b, err := io.ReadAll(reader)
	return b, err
}

func parseFilter(rf string) (imagick.FilterType, error) {
	switch rf {
	case "":
		return imagick.FILTER_LANCZOS, nil
	case "point":
		return imagick.FILTER_POINT, nil
	case "box":
		return imagick.FILTER_BOX, nil
	case "triangle":
		return imagick.FILTER_TRIANGLE, nil
	case "hermite":
		return imagick.FILTER_HERMITE, nil
	case "hanning":
		return imagick.FILTER_HANNING, nil
	case "hamming":
		return imagick.FILTER_HAMMING, nil
	case "blackman":
		return imagick.FILTER_BLACKMAN, nil
	case "gaussian":
		return imagick.FILTER_GAUSSIAN, nil
	case "quadratic":
		return imagick.FILTER_QUADRATIC, nil
	case "cubic":
		return imagick.FILTER_CUBIC, nil
	case "catrom":
		return imagick.FILTER_CATROM, nil
	case "mitchell":
		return imagick.FILTER_MITCHELL, nil
	case "jinc":
		return imagick.FILTER_JINC, nil
	case "sinc":
		return imagick.FILTER_SINC, nil
	case "sinc_fast":
		return imagick.FILTER_SINC_FAST, nil
	case "kaiser":
		return imagick.FILTER_KAISER, nil
	case "welsh":
		return imagick.FILTER_WELSH, nil
	case "parzen":
		return imagick.FILTER_PARZEN, nil
	case "bohman":
		return imagick.FILTER_BOHMAN, nil
	case "bartlett":
		return imagick.FILTER_BARTLETT, nil
	case "lagrange":
		return imagick.FILTER_LAGRANGE, nil
	case "lanczos":
		return imagick.FILTER_LANCZOS, nil
	case "lanczos_sharp":
		return imagick.FILTER_LANCZOS_SHARP, nil
	case "lanczos2":
		return imagick.FILTER_LANCZOS2, nil
	case "lanczos2_sharp":
		return imagick.FILTER_LANCZOS2_SHARP, nil
	case "robidoux":
		return imagick.FILTER_ROBIDOUX, nil
	case "robidoux_sharp":
		return imagick.FILTER_ROBIDOUX_SHARP, nil
	case "cosine":
		return imagick.FILTER_COSINE, nil
	case "spline":
		return imagick.FILTER_SPLINE, nil
	default:
		return imagick.FILTER_LANCZOS, errors.New(fmt.Sprintf("Unrecognized filter %v", sanitize(rf)))
	}
}
