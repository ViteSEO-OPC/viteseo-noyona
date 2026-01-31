(function () {
    document.addEventListener('DOMContentLoaded', function () {
        // Accordion Logic
        const accordions = document.querySelectorAll('.ni-accordion-header');

        accordions.forEach(header => {
            header.addEventListener('click', function () {
                const item = this.parentElement;
                const body = item.querySelector('.ni-accordion-body');
                const toggle = this.querySelector('.ni-acc-toggle');

                // Toggle current
                if (body.classList.contains('open')) {
                    body.classList.remove('open');
                    item.classList.remove('ni-open');
                    toggle.innerHTML = '<i class="fa-solid fa-plus"></i>';
                } else {
                    body.classList.add('open');
                    item.classList.add('ni-open');
                    toggle.innerHTML = '<i class="fa-solid fa-xmark"></i>'; // Close icon
                }
            });
        });

        // Category Logic (Visual Toggle)
        const cats = document.querySelectorAll('.ni-cat-btn');
        cats.forEach(cat => {
            cat.addEventListener('click', function () {
                cats.forEach(c => c.classList.remove('active'));
                this.classList.add('active');
            });
        });
    });
})();
