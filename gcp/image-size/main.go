package main

import (
	"encoding/json"
	"fmt"
	"gopkg.in/gographics/imagick.v3/imagick"
	"log"
	"net/http"
	"os"
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
		http.Error(w, fmt.Sprintf("Error extracting image from request: %v", err), http.StatusBadRequest)
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

	w.Header().Set("Content-Type", "application/json")
	if err := json.NewEncoder(w).Encode(map[string]uint{"width": ow, "height": oh}); err != nil {
		http.Error(w, "Error writing response", http.StatusInternalServerError)
		log.Printf("Error writing response: %v", err)
	}
}
