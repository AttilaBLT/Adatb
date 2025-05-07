const toggleButton = document.querySelector('.dark-mode-toggle');
const body = document.body;

if (localStorage.getItem('darkMode') === 'enabled') {
    body.classList.add('dark-mode');
    toggleButton.textContent = "☀️";
} else {
    toggleButton.textContent = "🌙";
}

// Dark mode toggle esemény
toggleButton.addEventListener('click', () => {
    body.classList.toggle('dark-mode');

    if (body.classList.contains('dark-mode')) {
        localStorage.setItem('darkMode', 'enabled');
        toggleButton.textContent = "☀️";
    } else {
        localStorage.setItem('darkMode', 'disabled');
        toggleButton.textContent = "🌙";
    }
});
