package fmnas

import (
	"fmt"
	"log"
	"net/http"
	"strconv"

	"gopkg.in/gographics/imagick.v2/imagick"
)

func ResizeImage(w http.ResponseWriter, r *http.Request) {
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
	}

	mw := omw.Clone()
	ow := mw.GetImageWidth()
	oh := mw.GetImageHeight()
	nw := ow * nh / oh

	if err := mw.ResizeImage(nw, nh, imagick.FILTER_LANCZOS, 1); err != nil {
		http.Error(w, "Error resizing image", http.StatusInternalServerError)
		log.Printf("Error resizing image: %v", err)
	}

	if err := mw.SetImageCompression(imagick.COMPRESSION_JPEG); err != nil {
		http.Error(w, "Error setting compression type", http.StatusInternalServerError)
		log.Printf("Error setting compression type: %v", err)
	}

	if err := mw.SetImageCompressionQuality(80); err != nil {
		http.Error(w, "Error setting compression quality", http.StatusInternalServerError)
		log.Printf("Error setting compression quality: %v", err)
	}

	out := mw.GetImageBlob()

	w.Header().Set("Content-Type", "image/jpeg")
	if _, err := w.Write(out); err != nil {
		http.Error(w, "Error writing response", http.StatusInternalServerError)
		log.Printf("Error writing response: %v", err)
	}
}
