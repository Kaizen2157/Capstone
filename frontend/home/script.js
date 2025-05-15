const descriptions = document.querySelectorAll('.description');

descriptions.forEach((desc, index) => {
    // Add initial direction class
    if (index % 2 === 0) {
        desc.classList.add('from-left');
    } else {
        desc.classList.add('from-right');
    }
});

const observer = new IntersectionObserver(entries => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('animate');
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.2 });

descriptions.forEach(desc => {
    observer.observe(desc);
});