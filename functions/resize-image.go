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
