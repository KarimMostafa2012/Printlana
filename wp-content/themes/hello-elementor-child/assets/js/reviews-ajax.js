document.addEventListener('DOMContentLoaded', function () {
    // Get references to main elements
    const wrapper = document.querySelector('#ajax-reviews-wrapper');
    const spinner = document.querySelector('#reviews-loading-spinner');
    const loadMoreBtn = document.querySelector('#reviews-loadmore-btn');
    const paginationContainer = document.querySelector('#reviews-pagination');

    // Exit if essential elements are not found
    if (!wrapper || !spinner) return;

    // Initialize pagination variables
    const postsPerPage = parseInt(wrapper.getAttribute('data-posts-per-page'), 10);
    let totalPages = 1;
    let currentPage = 1;

    // Define pagination elements only if pagination container exists
    let prevArrow = null;
    let nextArrow = null;
    let paginationNumbers = null;

    if (paginationContainer) {
        prevArrow = paginationContainer.querySelector('.prev-arrow');
        nextArrow = paginationContainer.querySelector('.next-arrow');
        paginationNumbers = paginationContainer.querySelector('.pagination-numbers');
    }

    // Load More button click event
    if (loadMoreBtn) {
        // Get total number of pages initially
        fetchTotalPages();

        loadMoreBtn.addEventListener('click', function () {
            currentPage = parseInt(loadMoreBtn.getAttribute('data-current-page'), 10);
            const nextPage = currentPage + 1;

            // Load the next page of reviews
            loadReviews(nextPage, true);
        });
    }

    // Previous arrow click event
    if (prevArrow) {
        prevArrow.addEventListener('click', function () {
            if (currentPage > 1) {
                loadReviews(currentPage - 1, false);
            }
        });
    }

    // Next arrow click event
    if (nextArrow) {
        nextArrow.addEventListener('click', function () {
            if (currentPage < totalPages) {
                loadReviews(currentPage + 1, false);
            }
        });
    }

    // Click on numbered pagination buttons
    if (paginationContainer) {
        paginationContainer.addEventListener('click', function (e) {
            if (e.target.classList.contains('review-page-btn')) {
                const page = parseInt(e.target.getAttribute('data-page'), 10);
                loadReviews(page, false);
            }
        });
    }

    // Load reviews via AJAX
    function loadReviews(page, isLoadMore) {
        // Show the loading spinner
        spinner.style.display = 'block';

        // Prepare data for the request
        const formData = new FormData();
        formData.append('action', 'load_reviews');
        formData.append('page', page);
        formData.append('number', postsPerPage);
        formData.append('load_more', isLoadMore ? 'true' : 'false');
        formData.append('product_id', wrapper.dataset.productId);

        // Send AJAX request
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData,
        })
            .then(res => res.text())
            .then(data => {
                // Append or replace review HTML
                if (isLoadMore) {
                    wrapper.insertAdjacentHTML('beforeend', data);
                } else {
                    wrapper.innerHTML = data;
                }

                // Update current page and UI
                currentPage = page;
                if (loadMoreBtn) {
                    loadMoreBtn.setAttribute('data-current-page', page);
                }
                spinner.style.display = 'none';

                updatePaginationUI();
            });
    }

    // Update pagination UI elements
    function updatePaginationUI() {
        // Responsive maxVisible
        const isMobile = window.innerWidth < 576;
        const maxVisible = isMobile ? 2 : 3;

        // Show/hide Load More button
        if (loadMoreBtn) {
            loadMoreBtn.style.display = (currentPage < totalPages) ? 'inline-block' : 'none';
        }

        // Enable/disable arrows
        if (prevArrow) prevArrow.disabled = currentPage <= 1;
        if (nextArrow) nextArrow.disabled = currentPage >= totalPages;

        // Render pagination number buttons
        if (paginationNumbers) {
            paginationNumbers.innerHTML = '';

            let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let endPage = startPage + maxVisible - 1;

            if (endPage > totalPages) {
                endPage = totalPages;
                startPage = Math.max(1, endPage - maxVisible + 1);
            }

            // First page and ellipsis
            if (startPage > 1) {
                appendPageButton(1);
                if (startPage > 2) appendEllipsis();
            }

            // Middle buttons
            for (let i = startPage; i <= endPage; i++) {
                appendPageButton(i, i === currentPage);
            }

            // Last page and ellipsis
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) appendEllipsis();
                appendPageButton(totalPages);
            }
        }

        function appendPageButton(page, isActive = false) {
            const btn = document.createElement('button');
            btn.className = `review-page-btn ${isActive ? 'active' : ''}`;
            btn.setAttribute('data-page', page);
            btn.textContent = page;
            paginationNumbers.appendChild(btn);
        }

        function appendEllipsis() {
            const span = document.createElement('span');
            span.textContent = '...';
            span.className = 'pagination-ellipsis';
            paginationNumbers.appendChild(span);
        }
    }

    // Fetch total number of pages from server
    function fetchTotalPages() {
        const formData = new FormData();
        formData.append('action', 'get_reviews_total_pages');
        formData.append('number', postsPerPage);
        formData.append('product_id', wrapper.dataset.productId);

        // Send request to get total page count
        fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                totalPages = data.total_pages;
                updatePaginationUI();
            });
    }
});