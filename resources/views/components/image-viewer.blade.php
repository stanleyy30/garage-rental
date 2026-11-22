<div class="modal fade" id="imageViewer" tabindex="-1" aria-labelledby="viewerTitle" aria-hidden="true"> {{-- Pop-up, hidden until an image is opened --}}
    <div class="modal-dialog modal-xl modal-dialog-centered"> {{-- Wide box in the middle of the screen --}}
        <div class="modal-content"> {{-- White box --}}
            <div class="modal-header"> {{-- Top bar --}}
                <h2 class="modal-title h5" id="viewerTitle">Image</h2> {{-- Title, filled in by the script --}}
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button> {{-- Close button --}}
            </div> {{-- End of the top bar --}}
            <div class="modal-body"> {{-- Middle part --}}
                <div class="viewer-frame" id="viewerFrame"><img id="viewerImage" src="" alt="Uploaded image"></div> {{-- Dark box with the image --}}
                <p class="mt-3 mb-0 fs-5 text-center font-monospace" id="viewerCaption"></p> {{-- Text to compare with, e.g. the NIK the tenant typed --}}
            </div> {{-- End of the middle part --}}
            <div class="modal-footer justify-content-between"> {{-- Bottom bar --}}
                <div class="btn-group" role="group" aria-label="Turn the image"> {{-- Rotate buttons together --}}
                    <button type="button" class="btn btn-outline-secondary" data-viewer-turn="-90">&#8634; Turn left</button> {{-- Quarter turn to the left --}}
                    <button type="button" class="btn btn-outline-secondary" data-viewer-turn="0">Upright</button> {{-- Back to the original angle --}}
                    <button type="button" class="btn btn-outline-secondary" data-viewer-turn="90">Turn right &#8635;</button> {{-- Quarter turn to the right --}}
                </div> {{-- End of the rotate buttons --}}
                <a href="#" id="viewerOpen" target="_blank" class="btn btn-link">Open full size</a> {{-- Opens the image in a new tab --}}
            </div> {{-- End of the bottom bar --}}
        </div> {{-- End of the white box --}}
    </div> {{-- End of the dialog --}}
</div> {{-- End of the pop-up --}}
