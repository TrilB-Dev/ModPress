document.addEventListener('DOMContentLoaded', () => {
	const root = document;
	root.querySelectorAll('[data-modpress-count]').forEach((element) => {
		element.classList.add('modpress-count-ready');
	});
});
