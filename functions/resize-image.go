package fmnas

import (
	"fmt"
	"log"
	"net/http"
)

func ResizeImage(w http.ResponseWriter, r *http.Request) {
	const maxMemory = 20 * 1024 * 1024
	if err := r.ParseMultipartForm(maxMemory); err != nil {
		http.Error(w, "Unable to parse request", http.StatusBadRequest)
		log.Printf("Error parsing form: %v", err)
		return
	}

	defer func() {
		if err := r.MultipartForm.RemoveAll(); err != nil {
			http.Error(w, "Error removing temporary files", http.StatusInternalServerError)
			log.Printf("Error removing temporary files: %v", err)
		}
	}()

	for _, headers := range r.MultipartForm.File {
		for _, h := range headers {
			fmt.Fprintf(w, "File uploaded: %q (%v bytes)", h.Filename, h.Size)
			// Use h.Open() to read the contents of the file.
		}
	}

}
