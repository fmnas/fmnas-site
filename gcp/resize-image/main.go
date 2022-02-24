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
	"fmt"
	"log"
	"net/http"
	"os"
	"strconv"
	"strings"
	"time"

	"gopkg.in/gographics/imagick.v3/imagick"
)

func main() {
	log.Print("starting server...")
	http.HandleFunc("/", handler)

	// Determine port for HTTP service.
	port := os.Getenv("PORT")
	if port == "" {
		port = "8080"
		log.Printf("defaulting to port %s", port)
	}

	// Start HTTP server.
	log.Printf("listening on port %s", port)
	if err := http.ListenAndServe(":"+port, nil); err != nil {
		log.Fatal(err)
	}
}

func handler(w http.ResponseWriter, r *http.Request) {
	log.Printf("Got a resize-image request, %v", time.Now())

	if err := r.ParseMultipartForm(20 << 20); err != nil {
		http.Error(w, "Unable to parse request", http.StatusBadRequest)
		log.Printf("Error parsing request: %v", err)
		return
	}

	defer func() {
		if err := r.MultipartForm.RemoveAll(); err != nil {
			http.Error(w, "Error removing temporary files", http.StatusInternalServerError)
			log.Printf("Error removing temporary files: %v", err)
		}
	}()

	f, fh, err := r.FormFile("image")
	if err != nil {
		http.Error(w, "Error extracting image from request", http.StatusBadRequest)
		log.Printf("Error extracting image from request: %v", err)
		return
	}

	s := int(fh.Size)
	log.Printf("Request image is %v bytes", s)
	b := make([]byte, s)
	n, err := f.Read(b)
	if err != nil {
		http.Error(w, "Error reading image bytes", http.StatusInternalServerError)
		log.Printf("Error reading image bytes: %v", err)
		return
	}
	if n != s {
		http.Error(w, fmt.Sprintf("Read %v bytes, expected %v", n, s), http.StatusBadRequest)
		log.Printf("Read %v bytes, expected %v", n, s)
		return
	}

	h1 := r.PostFormValue("height")
	h2, err := strconv.Atoi(h1)
	if err != nil {
		http.Error(w, fmt.Sprintf("Error parsing supplied height %v", h1), http.StatusBadRequest)
		log.Printf("Error parsing supplied height %v", h1)
		return
	}
	nh := uint(h2)

	filter := imagick.FILTER_LANCZOS
	rf := strings.ToLower(r.PostFormValue("filter"))
	switch rf {
	case "":
		filter = imagick.FILTER_LANCZOS
	case "point":
		filter = imagick.FILTER_POINT
	case "box":
		filter = imagick.FILTER_BOX
	case "triangle":
		filter = imagick.FILTER_TRIANGLE
	case "hermite":
		filter = imagick.FILTER_HERMITE
	case "hanning":
		filter = imagick.FILTER_HANNING
	case "hamming":
		filter = imagick.FILTER_HAMMING
	case "blackman":
		filter = imagick.FILTER_BLACKMAN
	case "gaussian":
		filter = imagick.FILTER_GAUSSIAN
	case "quadratic":
		filter = imagick.FILTER_QUADRATIC
	case "cubic":
		filter = imagick.FILTER_CUBIC
	case "catrom":
		filter = imagick.FILTER_CATROM
	case "mitchell":
		filter = imagick.FILTER_MITCHELL
	case "jinc":
		filter = imagick.FILTER_JINC
	case "sinc":
		filter = imagick.FILTER_SINC
	case "sinc_fast":
		filter = imagick.FILTER_SINC_FAST
	case "kaiser":
		filter = imagick.FILTER_KAISER
	case "welsh":
		filter = imagick.FILTER_WELSH
	case "parzen":
		filter = imagick.FILTER_PARZEN
	case "bohman":
		filter = imagick.FILTER_BOHMAN
	case "bartlett":
		filter = imagick.FILTER_BARTLETT
	case "lagrange":
		filter = imagick.FILTER_LAGRANGE
	case "lanczos":
		filter = imagick.FILTER_LANCZOS
	case "lanczos_sharp":
		filter = imagick.FILTER_LANCZOS_SHARP
	case "lanczos2":
		filter = imagick.FILTER_LANCZOS2
	case "lanczos2_sharp":
		filter = imagick.FILTER_LANCZOS2_SHARP
	case "robidoux":
		filter = imagick.FILTER_ROBIDOUX
	case "robidoux_sharp":
		filter = imagick.FILTER_ROBIDOUX_SHARP
	case "cosine":
		filter = imagick.FILTER_COSINE
	case "spline":
		filter = imagick.FILTER_SPLINE
	default:
		http.Error(w, fmt.Sprintf("Unrecognized filter %v", rf), http.StatusBadRequest)
		log.Printf("Unrecognized filter %v", rf)
		return
	}

	imagick.Initialize()
	defer imagick.Terminate()
	mw := imagick.NewMagickWand()
	if err := mw.ReadImageBlob(b); err != nil {
		http.Error(w, "Error reading image", http.StatusBadRequest)
		log.Printf("Error reading image: %v", err)
		return
	}

	ow := mw.GetImageWidth()
	oh := mw.GetImageHeight()
	if oh < nh {
		nh = oh
	}
	nw := ow * nh / oh

	if err := mw.ResizeImage(nw, nh, filter); err != nil {
		http.Error(w, "Error resizing image", http.StatusInternalServerError)
		log.Printf("Error resizing image: %v", err)
		mw.Destroy()
		return
	}

	if err := mw.SetImageCompression(imagick.COMPRESSION_JPEG); err != nil {
		http.Error(w, "Error setting compression type", http.StatusInternalServerError)
		log.Printf("Error setting compression type: %v", err)
		mw.Destroy()
		return
	}

	if err := mw.SetImageCompressionQuality(90); err != nil {
		http.Error(w, "Error setting compression quality", http.StatusInternalServerError)
		log.Printf("Error setting compression quality: %v", err)
		mw.Destroy()
		return
	}

	if err := mw.SetFormat("jpg"); err != nil {
		http.Error(w, "Error setting format", http.StatusInternalServerError)
		log.Printf("Error setting format: %v", err)
		mw.Destroy()
		return
	}

	out := mw.GetImageBlob()
	log.Printf("Response image is %v bytes", len(out))

	w.Header().Set("Content-Type", "image/jpeg")
	if _, err := w.Write(out); err != nil {
		http.Error(w, "Error writing response", http.StatusInternalServerError)
		log.Printf("Error writing response: %v", err)
	}
	mw.Destroy()
}
