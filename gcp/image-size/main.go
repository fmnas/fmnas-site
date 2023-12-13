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
	"gopkg.in/gographics/imagick.v3/imagick"
	"log"
	"net/http"
	"os"
	"time"
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
	log.Printf("Got an image-size request, %v", time.Now())

	var b []byte

	if r.Method == "POST" {
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
			http.Error(w, fmt.Sprintf("Error extracting image from request: %v", err), http.StatusBadRequest)
			log.Printf("Error extracting image from request: %v", err)
			return
		}

		s := int(fh.Size)
		log.Printf("Payload is %v bytes", s)
		b = make([]byte, s)
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
	} else {
		ids := r.URL.Query()["object"]
		buckets := r.URL.Query()["bucket"]
		if len(ids) != 1 {
			http.Error(w, fmt.Sprintf("Expected 1 object, got %v", len(ids)), http.StatusBadRequest)
			log.Printf("ids: %v", ids)
			return
		}
		if len(buckets) != 1 {
			http.Error(w, fmt.Sprintf("Expected 1 bucket, got %v", len(ids)), http.StatusBadRequest)
			log.Printf("buckets: %v", buckets)
			return
		}
		id := ids[0]
		bucket := buckets[0]
		log.Printf("Reading image %v from storage", id)
		storageClient, err := storage.NewClient(context.Background())
		if err != nil {
			http.Error(w, "Error initializing storage client", http.StatusInternalServerError)
			log.Printf("storage.NewClient: %v", err)
			return
		}
		obj := storageClient.Bucket(bucket).Object(id)
		reader, err := obj.NewReader(context.Background())
		if errors.Is(err, storage.ErrObjectNotExist) {
			log.Printf("object does not exist: %v", id)
			http.Error(w, "Object not found", http.StatusNotFound)
			return
		}
		if err != nil {
			log.Printf("Error creating reader: %v", err)
			http.Error(w, "Error creating reader", http.StatusInternalServerError)
			return
		}
		attrs, err := obj.Attrs(context.Background())
		if err != nil {
			log.Printf("Error reading attrs: %v", err)
			http.Error(w, "Error reading object attrs", http.StatusInternalServerError)
			return
		}
		b = make([]byte, attrs.Size)
		_, err = reader.Read(b)
		if err != nil {
			log.Printf("Error reading object: %v", err)
			http.Error(w, "Error reading object", http.StatusInternalServerError)
			return
		}
		err = reader.Close()
		if err != nil {
			log.Printf("error closing reader: %v", err)
		}
	}

	imagick.Initialize()
	defer imagick.Terminate()
	mw := imagick.NewMagickWand()
	if err := mw.ReadImageBlob(b); err != nil {
		http.Error(w, "Error reading image", http.StatusBadRequest)
		log.Printf("Error reading image: %v", err)
		mw.Destroy()
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
	mw.Destroy()
}
