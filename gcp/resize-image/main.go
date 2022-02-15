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

	imagick.Initialize()
	defer imagick.Terminate()
	omw := imagick.NewMagickWand()
	if err := omw.ReadImageBlob(b); err != nil {
		http.Error(w, "Error reading image", http.StatusBadRequest)
		log.Printf("Error reading image: %v", err)
		return
	}

	mw := omw.Clone()
	ow := mw.GetImageWidth()
	oh := mw.GetImageHeight()
	if oh < nh {
		nh = oh
	}
	nw := ow * nh / oh

	if err := mw.ResizeImage(nw, nh, imagick.FILTER_LANCZOS); err != nil {
		http.Error(w, "Error resizing image", http.StatusInternalServerError)
		log.Printf("Error resizing image: %v", err)
		return
	}

	if err := mw.SetImageCompression(imagick.COMPRESSION_JPEG); err != nil {
		http.Error(w, "Error setting compression type", http.StatusInternalServerError)
		log.Printf("Error setting compression type: %v", err)
		return
	}

	if err := mw.SetImageCompressionQuality(90); err != nil {
		http.Error(w, "Error setting compression quality", http.StatusInternalServerError)
		log.Printf("Error setting compression quality: %v", err)
		return
	}

	if err := mw.SetFormat("jpg"); err != nil {
		http.Error(w, "Error setting format", http.StatusInternalServerError)
		log.Printf("Error setting format: %v", err)
		return
	}

	out := mw.GetImageBlob()

	w.Header().Set("Content-Type", "image/jpeg")
	if _, err := w.Write(out); err != nil {
		http.Error(w, "Error writing response", http.StatusInternalServerError)
		log.Printf("Error writing response: %v", err)
	}
}
