document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.blogs-view').forEach((block) => {
        const tocLinks = Array.from(block.querySelectorAll('.blogs-view__toc-link'));
        if (!tocLinks.length) {
            return;
        }

        const linkById = new Map();
        const headingTargets = tocLinks
            .map((link) => {
                const href = link.getAttribute('href');
                if (!href || href.charAt(0) !== '#') {
                    return null;
                }
                const id = decodeURIComponent(href.slice(1));
                if (!id) {
                    return null;
                }
                linkById.set(id, link);
                const target = document.getElementById(id);
                if (!target || !block.contains(target)) {
                    return null;
                }
                return target;
            })
            .filter(Boolean);

        if (!headingTargets.length) {
            return;
        }

        let activeId = '';
        const setActive = (id) => {
            if (!id || activeId === id) {
                return;
            }
            activeId = id;
            tocLinks.forEach((link) => {
                link.classList.toggle('is-active', link === linkById.get(id));
            });
        };

        setActive(headingTargets[0].id);

        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(
                (entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            setActive(entry.target.id);
                        }
                    });
                },
                {
                    rootMargin: '0px 0px -60% 0px',
                    threshold: 0.1,
                }
            );

            headingTargets.forEach((heading) => observer.observe(heading));
        }

        const shareBtn = block.querySelector('.blogs-view__pill--share');
        if (shareBtn && navigator.share) {
            shareBtn.addEventListener('click', (event) => {
                event.preventDefault();
                navigator
                    .share({
                        title: document.title,
                        url: shareBtn.href,
                    })
                    .catch(() => {});
            });
        }
    });
});
