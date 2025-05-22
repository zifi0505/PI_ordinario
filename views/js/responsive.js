// Agregar este código JavaScript al proyecto para manejar la funcionalidad responsive

document.addEventListener('DOMContentLoaded', function() {
    // Crear el botón toggle para el sidebar en móvil
    const sidebarToggle = document.createElement('button');
    sidebarToggle.className = 'sidebar-toggle';
    sidebarToggle.innerHTML = '☰';
    document.body.appendChild(sidebarToggle);
    
    // Agregar evento de click al botón
    sidebarToggle.addEventListener('click', function() {
        const sidebar = document.querySelector('.sidebar');
        sidebar.classList.toggle('active');
    });
    
    // Cerrar sidebar al hacer clic en cualquier lugar del contenido principal
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.addEventListener('click', function() {
            const sidebar = document.querySelector('.sidebar');
            if (sidebar.classList.contains('active')) {
                sidebar.classList.remove('active');
            }
        });
    }
    
    // Agregar atributos data-label a las celdas de la tabla para responsive
    const tablas = document.querySelectorAll('.tabla-publicaciones table');
    tablas.forEach(tabla => {
        const headers = tabla.querySelectorAll('th');
        const headerTexts = Array.from(headers).map(header => header.textContent);
        
        const rows = tabla.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            cells.forEach((cell, index) => {
                if (index < headerTexts.length) {
                    cell.setAttribute('data-label', headerTexts[index]);
                }
            });
        });
    });
});