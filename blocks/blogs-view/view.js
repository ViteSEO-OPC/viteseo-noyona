document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.blogs-view').forEach((block) => {
        // Keep sticky TOC below the sticky header (and admin bar) dynamically.
        const updateTocTop = () => {
            const header = document.querySelector('header.wp-block-template-part');
            if (!header) return;
            const rect = header.getBoundingClientRect();
            const bottom = Math.max(0, rect.bottom);
            // Small breathing room so TOC isn't glued to header.
            block.style.setProperty('--toc-top', Math.round(bottom + 16) + 'px');
        };

        updateTocTop();
        window.addEventListener('resize', updateTocTop, { passive: true });
        window.addEventListener('scroll', updateTocTop, { passive: true });

        const tocLinks = Array.from(block.querySelectorAll('.blogs-view__toc-link'));
        if (!tocLinks.length) {
            // Still allow share modal even if no TOC.
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

        if (headingTargets.length) {
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
        }

        // Share modal (desktop) with Web Share fallback (mobile).
        const shareBtn = block.querySelector('.blogs-view__pill--share[data-share-url]');
        const shareModal = block.querySelector('.blog-share-modal');
        const shareHint = shareModal ? shareModal.querySelector('.blog-share-modal__hint') : null;

        const setHint = (message) => {
            if (!shareHint) return;
            shareHint.textContent = message || '';
        };

        const buildShareLinks = (url) => {
            if (!shareModal) return;
            const encoded = encodeURIComponent(url);
            const encodedTitle = encodeURIComponent(document.title || '');
            const map = {
                facebook: 'https://www.facebook.com/sharer/sharer.php?u=' + encoded,
                x: 'https://x.com/intent/tweet?url=' + encoded + '&text=' + encodedTitle,
                linkedin: 'https://www.linkedin.com/sharing/share-offsite/?url=' + encoded,
            };

            shareModal.querySelectorAll('[data-share-platform]').forEach((el) => {
                const platform = el.getAttribute('data-share-platform');
                if (!platform) return;
                if (el.tagName === 'A') {
                    el.setAttribute('href', map[platform] || '#');
                }
            });
        };

        const closeShare = () => {
            if (!shareModal) return;
            shareModal.hidden = true;
            document.body.style.overflow = '';
            setHint('');
            delete shareModal.dataset.shareUrl;
        };

        const openShare = (url) => {
            if (!shareModal || !url || url === '#') return;
            setHint('');
            buildShareLinks(url);
            shareModal.hidden = false;
            document.body.style.overflow = 'hidden';
            shareModal.dataset.shareUrl = url;

            const closeBtn = shareModal.querySelector('.blog-share-modal__close');
            if (closeBtn) closeBtn.focus({ preventScroll: true });
        };

        const copyToClipboard = async (text) => {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (e) {
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'fixed';
                textarea.style.left = '-9999px';
                document.body.appendChild(textarea);
                textarea.select();
                const ok = document.execCommand('copy');
                document.body.removeChild(textarea);
                return ok;
            }
        };

        if (shareBtn) {
            shareBtn.addEventListener('click', (event) => {
                event.preventDefault();
                const url = shareBtn.dataset.shareUrl || '';
                // Always open the custom modal so platform options
                // (Facebook, Instagram copy flow, X, LinkedIn, Copy)
                // are consistently available across devices.
                if (shareModal) {
                    openShare(url);
                    return;
                }

                // Fallback only when modal is unavailable.
                if (navigator.share) {
                    navigator
                        .share({
                            title: document.title,
                            url,
                        })
                        .catch(() => {});
                }
            });
        }

        if (shareModal) {
            shareModal.addEventListener('click', async (e) => {
                const target = e.target;
                if (!(target instanceof Element)) return;

                const closeEl = target.closest('[data-share-close]');
                if (closeEl) {
                    e.preventDefault();
                    closeShare();
                    return;
                }

                const item = target.closest('[data-share-platform]');
                if (!item) return;

                const platform = item.getAttribute('data-share-platform');
                const url = shareModal.dataset.shareUrl || '';
                if (!platform || !url) return;

                if (platform === 'copy') {
                    e.preventDefault();
                    const ok = await copyToClipboard(url);
                    setHint(ok ? 'Link copied!' : 'Could not copy link.');
                }

                if (platform === 'instagram') {
                    e.preventDefault();
                    let sharedViaNative = false;
                    if (navigator.share) {
                        try {
                            await navigator.share({
                                title: document.title,
                                url,
                            });
                            sharedViaNative = true;
                            setHint('Opened your native share options.');
                        } catch (err) {
                            // User canceled or browser blocked; continue to copy/open flow.
                        }
                    }

                    if (!sharedViaNative) {
                        const ok = await copyToClipboard(url);
                        window.open('https://www.instagram.com/', '_blank', 'noopener,noreferrer');
                        setHint(
                            ok
                                ? 'Link copied. Instagram opened in a new tab — paste the link in your post, story, or DM.'
                                : 'Instagram opened. Copy failed — please copy the URL manually.'
                        );
                    }
                }
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !shareModal.hidden) closeShare();
            });
        }
    });
});
