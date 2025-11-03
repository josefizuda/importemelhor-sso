/**
 * Banner Carousel - Importe Melhor SSO
 * Auto-rotating banner carousel with manual controls
 */

class BannerCarousel {
    constructor(containerSelector, options = {}) {
        this.container = document.querySelector(containerSelector);
        if (!this.container) {
            console.warn('Carousel container not found:', containerSelector);
            return;
        }

        this.options = {
            autoPlay: true,
            interval: 5000,
            transition: 500,
            pauseOnHover: true,
            ...options
        };

        this.currentIndex = 0;
        this.slides = [];
        this.indicators = [];
        this.isPlaying = this.options.autoPlay;
        this.intervalId = null;

        this.init();
    }

    init() {
        this.slides = Array.from(this.container.querySelectorAll('.banner-slide'));

        if (this.slides.length === 0) {
            console.warn('No slides found in carousel');
            return;
        }

        this.setupControls();
        this.setupIndicators();
        this.setupEventListeners();

        // Show first slide
        this.showSlide(0);

        // Start autoplay
        if (this.options.autoPlay) {
            this.play();
        }
    }

    setupControls() {
        const controls = this.container.querySelector('.carousel-controls');
        if (!controls) return;

        const prevBtn = controls.querySelector('.carousel-btn-prev');
        const nextBtn = controls.querySelector('.carousel-btn-next');

        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.prev();
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.next();
            });
        }
    }

    setupIndicators() {
        const indicatorsContainer = this.container.querySelector('.carousel-indicators');
        if (!indicatorsContainer) return;

        indicatorsContainer.innerHTML = '';

        this.slides.forEach((slide, index) => {
            const indicator = document.createElement('button');
            indicator.className = 'carousel-indicator';
            indicator.setAttribute('aria-label', `Slide ${index + 1}`);
            indicator.addEventListener('click', () => this.goToSlide(index));

            indicatorsContainer.appendChild(indicator);
            this.indicators.push(indicator);
        });
    }

    setupEventListeners() {
        if (this.options.pauseOnHover) {
            this.container.addEventListener('mouseenter', () => this.pause());
            this.container.addEventListener('mouseleave', () => this.play());
        }

        // Touch/Swipe support
        let touchStartX = 0;
        let touchEndX = 0;

        this.container.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });

        this.container.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            this.handleSwipe(touchStartX, touchEndX);
        }, { passive: true });
    }

    handleSwipe(startX, endX) {
        const threshold = 50;
        const diff = startX - endX;

        if (Math.abs(diff) > threshold) {
            if (diff > 0) {
                this.next();
            } else {
                this.prev();
            }
        }
    }

    showSlide(index) {
        // Remove active class from all slides
        this.slides.forEach(slide => slide.classList.remove('active'));
        this.indicators.forEach(indicator => indicator.classList.remove('active'));

        // Add active class to current slide
        if (this.slides[index]) {
            this.slides[index].classList.add('active');
        }

        if (this.indicators[index]) {
            this.indicators[index].classList.add('active');
        }

        this.currentIndex = index;
    }

    next() {
        const nextIndex = (this.currentIndex + 1) % this.slides.length;
        this.goToSlide(nextIndex);
    }

    prev() {
        const prevIndex = (this.currentIndex - 1 + this.slides.length) % this.slides.length;
        this.goToSlide(prevIndex);
    }

    goToSlide(index) {
        if (index === this.currentIndex) return;

        this.showSlide(index);

        // Reset autoplay timer
        if (this.isPlaying) {
            this.pause();
            this.play();
        }
    }

    play() {
        if (this.slides.length <= 1) return;

        this.isPlaying = true;
        this.intervalId = setInterval(() => {
            this.next();
        }, this.options.interval);
    }

    pause() {
        this.isPlaying = false;
        if (this.intervalId) {
            clearInterval(this.intervalId);
            this.intervalId = null;
        }
    }

    destroy() {
        this.pause();
        // Remove event listeners if needed
    }
}

// Auto-initialize carousel on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    const carouselElement = document.querySelector('.banner-carousel');
    if (carouselElement) {
        window.bannerCarousel = new BannerCarousel('.banner-carousel', {
            autoPlay: true,
            interval: 5000,
            pauseOnHover: true
        });
    }
});
