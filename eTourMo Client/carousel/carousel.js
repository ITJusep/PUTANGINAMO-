let currentIndex = 0;

function showNextSlide() {
  const slides = document.querySelectorAll('.carousel-item');
  const totalSlides = slides.length;

  // Remove the "active" class from the current slide
  slides[currentIndex].classList.remove('active');

  // Move to the next slide
  currentIndex = (currentIndex + 1) % totalSlides;

  // Add the "active" class to the new slide
  slides[currentIndex].classList.add('active');
}

// Automatically switch slides every 5 seconds
setInterval(showNextSlide, 5000);
