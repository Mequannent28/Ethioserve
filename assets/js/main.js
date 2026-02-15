// Main JS for EthioServe
document.addEventListener('DOMContentLoaded', function() {
    console.log('EthioServe Platform Loaded Successfully');
    
    // Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            document.querySelector(this.getAttribute('href')).scrollIntoView({
                behavior: 'smooth'
            });
        });
    });
});
