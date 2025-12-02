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
                // We use a higher scale for better quality
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
                showCover: true, // This is key for the "closed book" start
                mobileScrollSupport: false
            });

            pageFlip.loadFromHTML(document.querySelectorAll('.page'));

            // Controls
            prevBtn.addEventListener('click', () => {
                pageFlip.flipPrev();
            });

            nextBtn.addEventListener('click', () => {
                pageFlip.flipNext();
            });

            // Optional: Update UI on flip
            pageFlip.on('flip', (e) => {
                // console.log('Current page: ' + e.data);
            });

        });

    }).catch(function(error) {
        console.error('Error loading PDF:', error);
        container.innerHTML = '<div class="alert alert-danger">Error loading document. Please try again later.</div>';
    });
});
