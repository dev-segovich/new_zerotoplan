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
                const scale = 1.5; 
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
            const pageFlip = new St.PageFlip(container, {
                width: baseWidth, 
                height: baseHeight,
                size: 'stretch',
                maxShadowOpacity: 0.5,
                showCover: true, // Shows first page on the right (like a closed book)
                mobileScrollSupport: false,
                useMouseEvents: true, // Enable for dragging
                clickEventForward: true, // Enable click on pages
                swipeDistance: 30 // Enable swipe/drag gestures
            });

            pageFlip.loadFromHTML(document.querySelectorAll('.page'));

            // Function to center the flipbook when on cover page
            function updateFlipbookPosition() {
                const currentPage = pageFlip.getCurrentPageIndex();
                const wrapper = container.querySelector('.stf__wrapper');
                
                if (currentPage === 0) {
                    // On cover page - center the single page
                    container.classList.add('showing-cover');
                    
                    if (wrapper) {
                        wrapper.style.transform = 'translateX(-25%)';
                        wrapper.style.transition = 'transform 0.3s ease';
                    }
                } else {
                    // On other pages - show both pages (open book)
                    container.classList.remove('showing-cover');
                    
                    if (wrapper) {
                        wrapper.style.transform = 'translateX(0)';
                        wrapper.style.transition = 'transform 0.3s ease';
                    }
                }
            }

            // Initial position
            setTimeout(updateFlipbookPosition, 500);

            // Controls
            prevBtn.addEventListener('click', () => {
                pageFlip.flipPrev();
                // Update position immediately after click, before animation completes
                setTimeout(updateFlipbookPosition, 50);
            });

            nextBtn.addEventListener('click', () => {
                // Update position IMMEDIATELY when clicking next from cover
                const currentPage = pageFlip.getCurrentPageIndex();
                if (currentPage === 0) {
                    const wrapper = container.querySelector('.stf__wrapper');
                    if (wrapper) {
                        wrapper.style.transform = 'translateX(0)';
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
                // When user starts dragging from cover, immediately center
                const currentPage = pageFlip.getCurrentPageIndex();
                if (currentPage === 0 && e.data === 'flipping') {
                    const wrapper = container.querySelector('.stf__wrapper');
                    if (wrapper) {
                        wrapper.style.transform = 'translateX(0)';
                        container.classList.remove('showing-cover');
                    }
                }
            });

            // Fullscreen functionality
            const fullscreenBtn = document.getElementById('fullscreen-btn');
            const closeFullscreenBtn = document.getElementById('close-fullscreen-btn');
            const flipbookSection = document.getElementById('flipbook');

            if (fullscreenBtn && closeFullscreenBtn && flipbookSection) {
                fullscreenBtn.addEventListener('click', () => {
                    flipbookSection.classList.add('fullscreen-mode');
                    document.body.style.overflow = 'hidden'; // Block scroll
                    // Trigger resize to update flipbook dimensions
                    setTimeout(() => {
                        pageFlip.update(); // Try update method if available
                        window.dispatchEvent(new Event('resize')); // Fallback
                        updateFlipbookPosition();
                    }, 100);
                });

                closeFullscreenBtn.addEventListener('click', () => {
                    flipbookSection.classList.remove('fullscreen-mode');
                    document.body.style.overflow = ''; // Restore scroll
                    // Trigger resize to update flipbook dimensions
                    setTimeout(() => {
                        pageFlip.update(); 
                        window.dispatchEvent(new Event('resize'));
                        updateFlipbookPosition();
                    }, 100);
                });
                
                // Close on Escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && flipbookSection.classList.contains('fullscreen-mode')) {
                        flipbookSection.classList.remove('fullscreen-mode');
                        document.body.style.overflow = ''; // Restore scroll
                        setTimeout(() => {
                            pageFlip.update();
                            window.dispatchEvent(new Event('resize'));
                            updateFlipbookPosition();
                        }, 100);
                    }
                });
            }

        });

    }).catch(function(error) {
        console.error('Error loading PDF:', error);
        container.innerHTML = '<div class="alert alert-danger">Error loading document. Please try again later.</div>';
    });
});
