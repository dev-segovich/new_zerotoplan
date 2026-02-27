document.addEventListener('DOMContentLoaded', function() {
    const url = 'assets/documents/binder_2900_imma.pdf';
    const container = document.getElementById('flipbook-container');
    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');

    // Check if libraries are loaded
    if (typeof pdfjsLib === 'undefined' || typeof St === 'undefined') {
        console.error('PDF.js or PageFlip library not loaded.');
        return;
    }

    // Set worker
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/2.16.105/pdf.worker.min.js';

    // Load PDF
    pdfjsLib.getDocument(url).promise.then(function(pdf) {
        console.log('PDF loaded');
        const numPages = pdf.numPages;
        let promises = [];

        // Fetch all pages
        for (let i = 1; i <= numPages; i++) {
            promises.push(pdf.getPage(i).then(function(page) {
                // Increased scale for fullscreen quality
                const scale = 2.0; 
                const viewport = page.getViewport({ scale: scale });
                const canvas = document.createElement('canvas');
                const context = canvas.getContext('2d');
                canvas.height = viewport.height;
                canvas.width = viewport.width;
                
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                
                return page.render(renderContext).promise.then(() => {
                    return { 
                        canvas: canvas, 
                        index: i,
                        width: viewport.width,
                        height: viewport.height
                    };
                });
            }));
        }

        Promise.all(promises).then(function(results) {
            // Sort by index
            results.sort((a, b) => a.index - b.index);
            
            const firstPage = results[0];
            const baseWidth = firstPage.width;
            const baseHeight = firstPage.height;
            
            // Create DOM elements
            results.forEach(item => {
                const pageDiv = document.createElement('div');
                pageDiv.className = 'page';
                pageDiv.appendChild(item.canvas);
                container.appendChild(pageDiv);
            });

            // Initialize PageFlip
            // We use 'contain' mode or similar to ensure it fits in the screen
            const pageFlip = new St.PageFlip(container, {
                width: baseWidth, 
                height: baseHeight,
                size: 'stretch', // 'stretch' allows it to fit the parent container
                maxShadowOpacity: 0.5,
                showCover: true, 
                mobileScrollSupport: false,
                useMouseEvents: true,
                clickEventForward: true,
                swipeDistance: 30
            });

            pageFlip.loadFromHTML(document.querySelectorAll('.page'));

            // ===== ZOOM FUNCTIONALITY =====
            let currentZoom = 1.0;
            const minZoom = 0.5;
            const maxZoom = 3.0;
            const zoomStep = 0.25;
            
            // Pan support when zoomed
            let isPanning = false;
            let startX = 0;
            let startY = 0;
            let translateX = 0;
            let translateY = 0;
            
            const wrapper = container.querySelector('.stf__wrapper');
            const zoomInBtn = document.getElementById('zoom-in-btn');
            const zoomOutBtn = document.getElementById('zoom-out-btn');
            const zoomResetBtn = document.getElementById('zoom-reset-btn');
            
            function updateZoom(newZoom) {
                currentZoom = Math.max(minZoom, Math.min(maxZoom, newZoom));
                
                if (wrapper) {
                    // Apply zoom transform
                    const currentPage = pageFlip.getCurrentPageIndex();
                    let baseTransform = '';
                    
                    // Preserve cover page centering if on first page
                    if (currentPage === 0) {
                        baseTransform = 'translateX(-25%)';
                    }
                    
                    // Apply zoom and pan
                    wrapper.style.transform = `${baseTransform} scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
                    
                    // Add/remove zoomed class for cursor styling
                    if (currentZoom > 1.0) {
                        container.classList.add('zoomed');
                        wrapper.classList.add('zoomed');
                    } else {
                        container.classList.remove('zoomed');
                        wrapper.classList.remove('zoomed');
                        // Reset pan when zoom returns to 1.0
                        translateX = 0;
                        translateY = 0;
                    }
                }
                
                // Update zoom display
                const zoomPercent = Math.round(currentZoom * 100);
                zoomResetBtn.textContent = `${zoomPercent}%`;
                
                // Update button states
                zoomInBtn.disabled = currentZoom >= maxZoom;
                zoomOutBtn.disabled = currentZoom <= minZoom;
            }
            
            // Zoom In
            zoomInBtn.addEventListener('click', () => {
                updateZoom(currentZoom + zoomStep);
            });
            
            // Zoom Out
            zoomOutBtn.addEventListener('click', () => {
                updateZoom(currentZoom - zoomStep);
            });
            
            // Reset Zoom
            zoomResetBtn.addEventListener('click', () => {
                currentZoom = 1.0;
                translateX = 0;
                translateY = 0;
                updateZoom(1.0);
            });
            
            // Mouse wheel zoom
            container.addEventListener('wheel', (e) => {
                e.preventDefault();
                
                const zoomDelta = e.deltaY > 0 ? -0.1 : 0.1;
                updateZoom(currentZoom + zoomDelta);
            }, { passive: false });
            
            // Panning support when zoomed
            container.addEventListener('mousedown', (e) => {
                if (currentZoom > 1.0) {
                    isPanning = true;
                    startX = e.clientX - translateX;
                    startY = e.clientY - translateY;
                    e.preventDefault();
                }
            });
            
            container.addEventListener('mousemove', (e) => {
                if (isPanning && currentZoom > 1.0) {
                    translateX = e.clientX - startX;
                    translateY = e.clientY - startY;
                    
                    const currentPage = pageFlip.getCurrentPageIndex();
                    let baseTransform = currentPage === 0 ? 'translateX(-25%)' : '';
                    wrapper.style.transform = `${baseTransform} scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
                }
            });
            
            container.addEventListener('mouseup', () => {
                isPanning = false;
            });
            
            container.addEventListener('mouseleave', () => {
                isPanning = false;
            });
            
            // Touch support for pinch-to-zoom and panning
            let touchStartDistance = 0;
            let touchStartZoom = 1.0;
            let isTouchPanning = false;
            let touchStartX = 0;
            let touchStartY = 0;
            
            container.addEventListener('touchstart', (e) => {
                if (e.touches.length === 2) {
                    // Pinch to zoom
                    const touch1 = e.touches[0];
                    const touch2 = e.touches[1];
                    touchStartDistance = Math.hypot(
                        touch2.clientX - touch1.clientX,
                        touch2.clientY - touch1.clientY
                    );
                    touchStartZoom = currentZoom;
                    e.preventDefault();
                } else if (e.touches.length === 1 && currentZoom > 1.0) {
                    // Single touch panning when zoomed
                    isTouchPanning = true;
                    touchStartX = e.touches[0].clientX - translateX;
                    touchStartY = e.touches[0].clientY - translateY;
                }
            }, { passive: false });
            
            container.addEventListener('touchmove', (e) => {
                if (e.touches.length === 2) {
                    // Pinch to zoom
                    const touch1 = e.touches[0];
                    const touch2 = e.touches[1];
                    const currentDistance = Math.hypot(
                        touch2.clientX - touch1.clientX,
                        touch2.clientY - touch1.clientY
                    );
                    
                    const scale = currentDistance / touchStartDistance;
                    updateZoom(touchStartZoom * scale);
                    e.preventDefault();
                } else if (e.touches.length === 1 && isTouchPanning && currentZoom > 1.0) {
                    // Single touch panning
                    translateX = e.touches[0].clientX - touchStartX;
                    translateY = e.touches[0].clientY - touchStartY;
                    
                    const currentPage = pageFlip.getCurrentPageIndex();
                    let baseTransform = currentPage === 0 ? 'translateX(-25%)' : '';
                    wrapper.style.transform = `${baseTransform} scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
                    e.preventDefault();
                }
            }, { passive: false });
            
            container.addEventListener('touchend', () => {
                touchStartDistance = 0;
                isTouchPanning = false;
            });

            // Function to center the flipbook when on cover page
            function updateFlipbookPosition() {
                const currentPage = pageFlip.getCurrentPageIndex();
                
                if (currentPage === 0) {
                    // On cover page - center the single page
                    container.classList.add('showing-cover');
                    
                    if (wrapper) {
                        const baseTransform = 'translateX(-25%)';
                        wrapper.style.transform = `${baseTransform} scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
                        wrapper.style.transition = 'transform 0.3s ease';
                    }
                } else {
                    // On other pages - show both pages (open book)
                    container.classList.remove('showing-cover');
                    
                    if (wrapper) {
                        wrapper.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
                        wrapper.style.transition = 'transform 0.3s ease';
                    }
                }
            }

            // Initial position
            setTimeout(updateFlipbookPosition, 500);

            // Controls
            prevBtn.addEventListener('click', () => {
                pageFlip.flipPrev();
                setTimeout(updateFlipbookPosition, 50);
            });

            nextBtn.addEventListener('click', () => {
                const currentPage = pageFlip.getCurrentPageIndex();
                if (currentPage === 0) {
                    if (wrapper) {
                        wrapper.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
                        container.classList.remove('showing-cover');
                    }
                }
                pageFlip.flipNext();
            });

            // Update position on manual flip (drag)
            pageFlip.on('flip', (e) => {
                setTimeout(updateFlipbookPosition, 100);
            });

            // Detect when flip animation starts (during drag)
            pageFlip.on('changeState', (e) => {
                const currentPage = pageFlip.getCurrentPageIndex();
                if (currentPage === 0 && e.data === 'flipping') {
                    if (wrapper) {
                        wrapper.style.transform = `scale(${currentZoom}) translate(${translateX}px, ${translateY}px)`;
                        container.classList.remove('showing-cover');
                    }
                }
            });

        });

    }).catch(function(error) {
        console.error('Error loading PDF:', error);
        container.innerHTML = '<div class="alert alert-danger">Error loading document. Please try again later.</div>';
    });
});
