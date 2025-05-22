 <?php

require '../config/db.php';
require '../azure/config.php';
require '../azure/azure-translator.php';

// 1) Idioma solicitado
$lang = $_GET['lang'] ?? 'es';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_comentario_id'])) {
    $id_comentario = (int)$_POST['eliminar_comentario_id'];

    try {
        $stmt = $pdo->prepare("DELETE FROM comentarios WHERE id_comentario = :id_comentario");
        $stmt->execute([':id_comentario' => $id_comentario]);

        // Redirigir para evitar reenvío del formulario
        header("Location: admin.php?lang=$lang");
        exit;
    } catch (PDOException $e) {
        die("Error al eliminar el comentario: " . $e->getMessage());
    }
}

// Iniciar buffer para contenido traducible
ob_start();

// Obtener publicaciones activas con filtro de categoría
$categoriaFiltro = $_GET['categoria'] ?? null;

try {
    $sql = "SELECT id_noticia, fecha, titular, descripcion_corta, imagen_principal FROM publicaciones WHERE archivada = 0";
    if (!empty($categoriaFiltro)) {
        $sql .= " AND id_categoria = :categoria";
    }
    $sql .= " ORDER BY fecha DESC";

    $stmt = $pdo->prepare($sql);
    if (!empty($categoriaFiltro)) {
        $stmt->bindParam(':categoria', $categoriaFiltro, PDO::PARAM_INT);
    }
    $stmt->execute();
    $publicaciones = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener publicaciones: " . $e->getMessage());
}

// Obtener publicaciones archivadas
try {
    $sql = "SELECT id_noticia, fecha, titular, descripcion_corta, imagen_principal FROM publicaciones WHERE archivada = 1 ORDER BY fecha DESC";
    $stmt = $pdo->query($sql);
    $archivadas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener publicaciones archivadas: " . $e->getMessage());
}

// Obtener datos del administrador
try {
    $sqlAdmin = "SELECT nombre, correo FROM admin WHERE id_admin = 1";
    $stmtAdmin = $pdo->query($sqlAdmin);
    $admin = $stmtAdmin->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener datos del administrador: " . $e->getMessage());
}

// Obtener categorías para el formulario
try {
    $sqlCategorias = "SELECT id_categoria, nombre FROM categorias";
    $stmtCategorias = $pdo->query($sqlCategorias);
    $categorias = $stmtCategorias->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener categorías: " . $e->getMessage());
}

// Si se recibe un ID para editar, cargar los datos de esa publicación
$editar_publicacion = null;
if (isset($_GET['editar']) && is_numeric($_GET['editar'])) {
    $id_editar = $_GET['editar'];
    try {
        $sqlEditar = "SELECT * FROM publicaciones WHERE id_noticia = ?";
        $stmtEditar = $pdo->prepare($sqlEditar);
        $stmtEditar->execute([$id_editar]);
        $editar_publicacion = $stmtEditar->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Error al obtener la publicación para editar: " . $e->getMessage());
    }
}

// Obtener comentarios
try {
    $sqlComentarios = "
        SELECT c.id_comentario, c.comentario, c.fecha_comentario, 
               CASE 
                   WHEN a.nombre IS NOT NULL THEN a.nombre
                   ELSE u.nombre
               END AS autor,
               p.titular AS publicacion
        FROM comentarios c
        LEFT JOIN admin a ON c.id_usuario = a.id_admin
        LEFT JOIN usuarios u ON c.id_usuario = u.id_usuario
        LEFT JOIN publicaciones p ON c.id_noticia = p.id_noticia
        ORDER BY c.fecha_comentario DESC
    ";
    $stmtComentarios = $pdo->query($sqlComentarios);
    $comentarios = $stmtComentarios->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener comentarios: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="../views/css/admin.css">
    <link rel="stylesheet" href="../views/css/font/font.css">

    <!-- Sincronizar ?lang ⇆ localStorage -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const params = new URLSearchParams(window.location.search);
            const urlLang = params.get('lang');
            const savedLang = localStorage.getItem('lang');
            if (savedLang && !urlLang) {
                params.set('lang', savedLang);
                window.location.search = params.toString();
            }
        });
    </script>

    <style>
        /* Estilos para el modal */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            z-index: 1000;
            backdrop-filter: blur(5px);
        }
        
        .modal-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            max-width: 80%;
            width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            z-index: 1001;
        }
        
        .modal-close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #333;
        }
        
        /* Estilos para el formulario */
        .modal-form {
            padding: 10px;
        }
        
        .modal-form h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }
        
        .submit-btn {
            background: var(--teal);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            width: 100%;
            margin-top: 20px;
        }
        
        /* Mejoras para el logo */
        .logo-container {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .logo-img {
            width: 60px;
            height: auto;
            margin-left: 10px;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .modal-container {
                width: 90%;
                max-height: 80vh;
            }
        }
        
        .dropdown-container {
            position: relative;
            margin-bottom: 15px;
        }
        
        .dropdown-header {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f9f9f9;
        }
        
        .dropdown-content {
            display: none;
            border: 1px solid #ddd;
            border-top: none;
            border-radius: 0 0 5px 5px;
            position: absolute;
            width: 100%;
            z-index: 1;
            background-color: white;
            max-height: 200px;
            overflow-y: auto;
        }
        
        /* Estilos para la previsualización de imágenes */
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            display: none;
        }
        
        .current-image {
            max-width: 100%;
            max-height: 200px;
            margin-top: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .filter-container {
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-container label {
            font-weight: bold;
            color: #333;
        }

        .filter-container select {
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .no-publicaciones {
            font-size: 18px;
            color: #555;
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo-container">
                <h2><a href="../index.php?lang=<?= $lang ?>">Voces Igualitarias</a></h2>
                <img src="../views/assets/Iguales.png" alt="Logo" class="logo-img">
            </div>
            <div class="user-info">
                <p><?php echo htmlspecialchars($admin['nombre']); ?></p>
                <p><?php echo htmlspecialchars($admin['correo']); ?></p>
            </div>
            <a href="../index.php?lang=<?= $lang ?>" class="back-link">⬅ <?= $lang==='es' ? 'Regresar al Blog' : 'Back to Blog' ?></a>
        </aside>

        <main class="main-content">
            <h1><?= $lang==='es' ? '¡Voces Igualitarias te da la bienvenida!' : 'Welcome to the Admin Panel!' ?></h1>
            <div class="button-container">
                <button id="toggleView" class="btn-ver-archivadas"><?= $lang==='es' ? '🗂 Ver archivadas' : '🗂 View Archived' ?></button>
                <button id="openModal" class="btn-nueva-publicacion"><?= $lang==='es' ? '➕ Nueva publicación' : '➕ New Publication' ?></button>
            </div>

            <!-- Tabla principal -->
            <div id="tablaActivas" class="tabla-publicaciones visible">
                <h2><?= $lang === 'es' ? 'Publicaciones activas' : 'Active Publications' ?></h2>
                <div class="filter-container">
                    <label for="filterCategoria"><?= $lang === 'es' ? 'Filtrar por categoría:' : 'Filter by category:' ?></label>
                    <select id="filterCategoria">
                        <option value="" <?= empty($categoriaFiltro) ? 'selected' : '' ?>>
                            <?= $lang === 'es' ? 'Todas las categorías' : 'All categories' ?>
                        </option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= htmlspecialchars($categoria['id_categoria']) ?>" <?= $categoriaFiltro == $categoria['id_categoria'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($categoria['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <?php if (empty($publicaciones)): ?>
                    <p class="no-publicaciones">
                        <?= $lang === 'es' ? 'No hay publicaciones en esta categoría.' : 'There are no publications in this category.' ?>
                    </p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th><?= $lang === 'es' ? 'Titular' : 'Headline' ?></th>
                                <th><?= $lang === 'es' ? 'Fecha' : 'Date' ?></th>
                                <th><?= $lang === 'es' ? 'Previsualizar' : 'Preview' ?></th>
                                <th><?= $lang === 'es' ? 'Editar' : 'Edit' ?></th>
                                <th><?= $lang === 'es' ? 'Eliminar' : 'Delete' ?></th>
                                <th><?= $lang === 'es' ? 'Archivar' : 'Archive' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($publicaciones as $pub): ?>
                                <tr>
                                    <td data-label="<?= $lang === 'es' ? 'Titular' : 'Headline' ?>"><?= htmlspecialchars($pub['titular']); ?></td>
                                    <td data-label="<?= $lang === 'es' ? 'Fecha' : 'Date' ?>"><?= date("d/m/Y", strtotime($pub['fecha'])); ?></td>
                                    <td data-label="<?= $lang === 'es' ? 'Previsualizar' : 'Preview' ?>">
                                        <a href="../views/layouts/ver_publicacion.php?id=<?= $pub['id_noticia']; ?>&lang=<?= $lang ?>">🔍</a>
                                    </td>
                                    <td data-label="<?= $lang === 'es' ? 'Editar' : 'Edit' ?>">
                                        <a href="?editar=<?= $pub['id_noticia']; ?>&lang=<?= $lang ?>" class="edit-link" data-id="<?= $pub['id_noticia']; ?>">✏️</a>
                                    </td>
                                    <td data-label="<?= $lang === 'es' ? 'Eliminar' : 'Delete' ?>">
                                        <a href="#" 
                                        onclick="mostrarConfirmacion('<?= $lang === 'es' ? '¿Estás seguro de eliminar esta publicación?' : 'Are you sure you want to delete this publication?' ?>', function() {
                                            window.location.href = 'eliminar_publicacion.php?id_noticia=<?= $pub['id_noticia']; ?>&lang=<?= $lang ?>';
                                        }); return false;">❌</a>
                                    </td>
                                    <td data-label="<?= $lang === 'es' ? 'Archivar' : 'Archive' ?>">
                                        <a href="#" 
                                        onclick="mostrarConfirmacion('<?= $lang === 'es' ? '¿Estás seguro de archivar esta publicación?' : 'Are you sure you want to archive this publication?' ?>', function() {
                                            window.location.href = 'archivar_publicacion.php?id_noticia=<?= $pub['id_noticia']; ?>&lang=<?= $lang ?>';
                                        }); return false;">📥</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Tabla archivadas -->
            <div id="tablaArchivadas" class="tabla-publicaciones hidden">
                <h2><?= $lang==='es' ? 'Publicaciones archivadas' : 'Archived Publications' ?></h2>
            <?php if (empty($archivadas)) { ?>
                <p class="no-archivadas"><?= $lang==='es' ? 'No hay publicaciones archivadas.' : 'No archived publications.' ?></p>
            <?php } else { ?>    
                <table>
                    <thead>
                        <tr>
                            <th><?= $lang==='es' ? 'Titular' : 'Headline' ?></th>
                            <th><?= $lang==='es' ? 'Fecha' : 'Date' ?></th>
                            <th><?= $lang==='es' ? 'Previsualizar' : 'Preview' ?></th>
                            <th><?= $lang==='es' ? 'Editar' : 'Edit' ?></th>
                            <th><?= $lang==='es' ? 'Eliminar' : 'Delete' ?></th>
                            <th><?= $lang==='es' ? 'Restaurar' : 'Restore' ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($archivadas as $arch): ?>
                            <tr>
                                <td data-label="<?= $lang==='es' ? 'Titular' : 'Headline' ?>"><?php echo htmlspecialchars($arch['titular']); ?></td>
                                <td data-label="<?= $lang==='es' ? 'Fecha' : 'Date' ?>"><?php echo date("d/m/Y", strtotime($arch['fecha'])); ?></td>
                                <td data-label="<?= $lang==='es' ? 'Previsualizar' : 'Preview' ?>">
                                    <a href="../views/layouts/ver_publicacion.php?id=<?php echo $arch['id_noticia']; ?>&lang=<?= $lang ?>">🔍</a>
                                </td>
                                <td data-label="<?= $lang==='es' ? 'Editar' : 'Edit' ?>">
                                    <a href="?editar=<?php echo $arch['id_noticia']; ?>&lang=<?= $lang ?>" class="edit-link" data-id="<?php echo $arch['id_noticia']; ?>">✏️</a>
                                </td>
                                <td data-label="<?= $lang==='es' ? 'Eliminar' : 'Delete' ?>">
                                    <a href="#" 
                                    onclick="mostrarConfirmacion('<?= $lang==='es' ? '¿Estás seguro de eliminar esta publicación?' : 'Are you sure you want to delete this publication?' ?>', function() {
                                        window.location.href = 'eliminar_publicacion.php?id_noticia=<?php echo $arch['id_noticia']; ?>&lang=<?= $lang ?>';
                                    }); return false;">❌</a>
                                </td>
                                <td data-label="<?= $lang==='es' ? 'Restaurar' : 'Restore' ?>">
                                    <a href="#" 
                                    onclick="mostrarConfirmacion('<?= $lang==='es' ? '¿Estás seguro de restaurar esta publicación?' : 'Are you sure you want to restore this publication?' ?>', function() {
                                        window.location.href = 'restaurar_publicacion.php?id_noticia=<?php echo $arch['id_noticia']; ?>&lang=<?= $lang ?>';
                                    }); return false;">♻️</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php } ?>    
            </div>

            <!-- Comentarios -->
            <div class="tabla-comentarios">
                <h2><?= $lang === 'es' ? 'Comentarios' : 'Comments' ?></h2>
                <?php if (empty($comentarios)): ?>
                    <p class="no-comentarios"><?= $lang === 'es' ? 'No hay comentarios disponibles en este momento.' : 'No comments available at the moment.' ?></p>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th><?= $lang === 'es' ? 'ID' : 'ID' ?></th>
                                <th><?= $lang === 'es' ? 'Comentario' : 'Comment' ?></th>
                                <th><?= $lang === 'es' ? 'Autor' : 'Author' ?></th>
                                <th><?= $lang === 'es' ? 'Fecha' : 'Date' ?></th>
                                <th><?= $lang === 'es' ? 'Publicación' : 'Publication' ?></th>
                                <th><?= $lang === 'es' ? 'Eliminar' : 'Delete' ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($comentarios as $comentario): ?>
                                <tr>
                                    <td><?= htmlspecialchars($comentario['id_comentario']) ?></td>
                                    <td><?= htmlspecialchars($comentario['comentario']) ?></td>
                                    <td><?= htmlspecialchars($comentario['autor']) ?></td>
                                    <td><?= date("d/m/Y H:i", strtotime($comentario['fecha_comentario'])) ?></td>
                                    <td><?= htmlspecialchars($comentario['publicacion']) ?></td>
                                    <td data-label="<?= $lang === 'es' ? 'Eliminar' : 'Delete' ?>">
                                        <a href="#" 
                                        onclick="mostrarConfirmacion('<?= $lang === 'es' ? '¿Estás seguro de eliminar este comentario?' : 'Are you sure you want to delete this comment?' ?>', function() {
                                            document.getElementById('eliminar-comentario-<?= $comentario['id_comentario'] ?>').submit();
                                        }); return false;">❌</a>
                                        <form id="eliminar-comentario-<?= $comentario['id_comentario'] ?>" method="POST">
                                            <input type="hidden" name="eliminar_comentario_id" value="<?= htmlspecialchars($comentario['id_comentario']) ?>">
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </main>
    </div>

    <!-- Modal para crear publicación -->
    <div id="publicacionModal" class="modal-overlay" <?php echo isset($_GET['editar']) ? 'style="display: block;"' : ''; ?>>
        <div class="modal-container">
            <span class="modal-close" id="closeModal">&times;</span>
            <div class="modal-form">
                <h2><?php echo $editar_publicacion ? ($lang==='es' ? 'Editar Publicación' : 'Edit Publication') : ($lang==='es' ? 'Formulario noticias' : 'News Form'); ?></h2>
                <form id="createPublicacionForm" action="<?php echo $editar_publicacion ? 'actualizar_publicacion.php' : 'guardar_publicacion.php'; ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="lang" value="<?= $lang ?>">
                    <?php if ($editar_publicacion): ?>
                        <input type="hidden" name="id_noticia" value="<?php echo $editar_publicacion['id_noticia']; ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="titular"><?= $lang==='es' ? 'Titular:' : 'Headline:' ?></label>
                        <input type="text" name="titular" id="titular" value="<?php echo $editar_publicacion ? htmlspecialchars($editar_publicacion['titular']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha"><?= $lang==='es' ? 'Fecha:' : 'Date:' ?></label>
                        <input type="date" name="fecha" id="fecha" value="<?php echo $editar_publicacion ? $editar_publicacion['fecha'] : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion_corta"><?= $lang==='es' ? 'Breve descripción:' : 'Brief description:' ?></label>
                        <input type="text" name="descripcion_corta" id="descripcion_corta" value="<?php echo $editar_publicacion ? htmlspecialchars($editar_publicacion['descripcion_corta']) : ''; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="imagen_principal">
                            <?php echo $editar_publicacion 
                                ? ($lang==='es' ? 'Cambiar imagen (opcional):' : 'Change image (optional):')
                                : ($lang==='es' ? 'Subir imagen:' : 'Upload image:'); ?>
                        </label>
                        <input type="file" name="imagen_principal" id="imagen_principal" accept="image/*" <?php echo $editar_publicacion ? '' : 'required'; ?>>
                        
                        <?php if ($editar_publicacion && !empty($editar_publicacion['imagen_principal'])): ?>
                            <div>
                                <p><?= $lang==='es' ? 'Imagen actual:' : 'Current image:' ?></p>
                                <img src="../views/assets/<?php echo htmlspecialchars($editar_publicacion['imagen_principal']); ?>" alt="Imagen actual" class="current-image">
                                <input type="hidden" name="imagen_actual" value="<?php echo htmlspecialchars($editar_publicacion['imagen_principal']); ?>">
                            </div>
                        <?php endif; ?>
                        <img id="imagenPreview" src="#" alt="<?= $lang==='es' ? 'Vista previa' : 'Preview' ?>" class="image-preview">
                    </div>
                    
                    <div class="form-group">
                        <label for="id_categoria"><?= $lang==='es' ? 'Categoría:' : 'Category:' ?></label>
                        <select name="id_categoria" id="id_categoria" required>
                            <option value=""><?= $lang==='es' ? 'Selecciona una categoría' : 'Select a category' ?></option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= htmlspecialchars($categoria['id_categoria']) ?>" <?php echo ($editar_publicacion && $editar_publicacion['id_categoria'] == $categoria['id_categoria']) ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="contenido"><?= $lang==='es' ? 'Contenido:' : 'Content:' ?></label>
                        <textarea name="contenido" id="contenido" rows="5" required><?php echo $editar_publicacion ? htmlspecialchars($editar_publicacion['contenido']) : ''; ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="referencia"><?= $lang==='es' ? 'Referencia APA:' : 'APA Reference:' ?></label>
                        <input type="text" name="referencia" id="referencia" value="<?php echo $editar_publicacion ? htmlspecialchars($editar_publicacion['referencia']) : ''; ?>">
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <?php echo $editar_publicacion 
                            ? ($lang==='es' ? 'Actualizar' : 'Update') 
                            : ($lang==='es' ? 'Subir' : 'Upload'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Incluir el archivo de notificaciones -->
    <?php include '../views/layouts/notificaciones.php'; ?>
    
    <script>
        // Toggle entre tablas activas y archivadas
        document.addEventListener('DOMContentLoaded', function() {
            const filterCategoria = document.getElementById('filterCategoria');
            filterCategoria.addEventListener('change', function() {
                const categoria = this.value;
                const params = new URLSearchParams(window.location.search);
                if (categoria) {
                    params.set('categoria', categoria);
                } else {
                    params.delete('categoria'); // Eliminar el filtro si no hay categoría seleccionada
                }
                window.location.search = params.toString();
            });
            
            // Guardar el idioma seleccionado en localStorage
            const params = new URLSearchParams(window.location.search);
            const urlLang = params.get('lang');
            if (urlLang) {
                localStorage.setItem('lang', urlLang);
            }
            
            const toggleBtn = document.getElementById('toggleView');
            const tablasActivas = document.getElementById('tablaActivas');
            const tablasArchivadas = document.getElementById('tablaArchivadas');
            
            let mostrandoActivas = true;
            
            toggleBtn.addEventListener('click', function() {
                if (mostrandoActivas) {
                    tablasActivas.classList.remove('visible');
                    tablasActivas.classList.add('hidden');
                    tablasArchivadas.classList.remove('hidden');
                    tablasArchivadas.classList.add('visible');
                    toggleBtn.textContent = '<?= $lang==='es' ? '🗂 Ver activas' : '🗂 View Active' ?>';
                } else {
                    tablasArchivadas.classList.remove('visible');
                    tablasArchivadas.classList.add('hidden');
                    tablasActivas.classList.remove('hidden');
                    tablasActivas.classList.add('visible');
                    toggleBtn.textContent = '<?= $lang==='es' ? '🗂 Ver archivadas' : '🗂 View Archived' ?>';
                }
                mostrandoActivas = !mostrandoActivas;
            });
            
            // Modal functions
            const modal = document.getElementById('publicacionModal');
            const openModalBtn = document.getElementById('openModal');
            const closeModalBtn = document.getElementById('closeModal');

             // Función para resetear el formulario
            function resetForm() {
                // Resetea todos los campos del formulario
                createForm.reset();

                // Oculta la vista previa de la imagen
                imagenPreview.style.display = 'none';

                // Limpiar manualmente los campos del formulario
                document.getElementById('titular').value = '';
                document.getElementById('fecha').value = '';
                document.getElementById('descripcion_corta').value = '';
                document.getElementById('contenido').value = '';
                document.getElementById('id_categoria').value = '';
                document.getElementById('imagen_principal').value = '';
                document.getElementById('referencia').value = '';

                // Ocultar la imagen actual si existe
                const currentImageContainer = document.querySelector('.current-image');
                if (currentImageContainer) {
                    currentImageContainer.parentElement.style.display = 'none';
                }

                // Restablecer el encabezado del modal y el botón de envío
                document.querySelector('.modal-form h2').textContent = '<?= $lang === "es" ? "Formulario noticias" : "News Form" ?>';
                document.querySelector('.submit-btn').textContent = '<?= $lang === "es" ? "Subir" : "Upload" ?>';
                createForm.action = 'guardar_publicacion.php';
            }
            
            openModalBtn.addEventListener('click', function() {
                resetForm(); // Resetear el formulario al abrir el modal
                // Resetear el formulario si se está abriendo para crear una nueva publicación
                document.getElementById('createPublicacionForm').reset();
                document.getElementById('imagenPreview').style.display = 'none';
                document.querySelector('.modal-form h2').textContent = '<?= $lang==='es' ? 'Formulario noticias' : 'News Form' ?>';
                document.getElementById('createPublicacionForm').action = 'guardar_publicacion.php';
                document.querySelector('.submit-btn').textContent = '<?= $lang==='es' ? 'Subir' : 'Upload' ?>';
                
                // Si hay una imagen actual en el formulario, ocultarla
                const currentImageContainer = document.querySelector('.current-image');
                if (currentImageContainer) {
                    currentImageContainer.parentElement.style.display = 'none';
                }
                
                modal.style.display = 'block';
                document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
            });
            
            closeModalBtn.addEventListener('click', function() {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto'; // Rehabilitar el scroll
                resetForm(); // Resetear el formulario al cerrar el modal

                // Resetear el formulario al cerrar el modal
                document.getElementById('createPublicacionForm').reset();
                document.getElementById('imagenPreview').style.display = 'none';

                // Si estábamos editando, eliminar el parámetro de la URL
                if (window.location.href.includes('editar=')) {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('editar');
                    history.pushState({}, '', url);
                }
            });

            // Cerrar el modal con la tecla Escape
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    if (modal.style.display === 'block') {
                        modal.style.display = 'none';
                        document.body.style.overflow = 'auto';
                        resetForm(); // Resetear el formulario al cerrar el modal

                        // Resetear el formulario al cerrar el modal
                        document.getElementById('createPublicacionForm').reset();
                        document.getElementById('imagenPreview').style.display = 'none';

                        // Si estábamos editando, eliminar el parámetro de la URL
                        if (window.location.href.includes('editar=')) {
                            const url = new URL(window.location.href);
                            url.searchParams.delete('editar');
                            history.pushState({}, '', url);
                        }
                    }
                }
            });
            
            // Close modal when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = 'none';
                    document.body.style.overflow = 'auto';
                    resetForm(); // Resetear el formulario al cerrar el modal

                    // Resetear el formulario al cerrar el modal
                    document.getElementById('createPublicacionForm').reset();
                    document.getElementById('imagenPreview').style.display = 'none';

                    // Si estábamos editando, eliminar el parámetro de la URL
                    if (window.location.href.includes('editar=')) {
                        const url = new URL(window.location.href);
                        url.searchParams.delete('editar');
                        history.pushState({}, '', url);
                    }
                }
            });
            
            // Vista previa de imagen
            const inputImagen = document.getElementById('imagen_principal');
            const imagenPreview = document.getElementById('imagenPreview');
            
            if (inputImagen) {
                inputImagen.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        var reader = new FileReader();
                        
                        reader.onload = function(e) {
                            imagenPreview.src = e.target.result;
                            imagenPreview.style.display = 'block';
                        }
                        
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
            
            // Form validation
            const createForm = document.getElementById('createPublicacionForm');
            if (createForm) {
                createForm.addEventListener('submit', function(event) {
                    const titular = document.getElementById('titular').value;
                    const fecha = document.getElementById('fecha').value;
                    const descripcion = document.getElementById('descripcion_corta').value;
                    const contenido = document.getElementById('contenido').value;
                    const categoria = document.getElementById('id_categoria').value;
                    
                    // Si es un nuevo post, la imagen es obligatoria
                    const imagen = document.getElementById('imagen_principal');
                    const isEdit = document.querySelector('input[name="id_noticia"]') !== null;
                    
                    if (!titular || !fecha || !descripcion || !contenido || !categoria) {
                        event.preventDefault();
                        alert('<?= $lang==='es' ? 'Por favor complete todos los campos obligatorios' : 'Please complete all required fields' ?>');
                    } else if (!isEdit && (!imagen.files || imagen.files.length === 0)) {
                        event.preventDefault();
                        alert('<?= $lang==='es' ? 'Por favor seleccione una imagen' : 'Please select an image' ?>');
                    }
                });
            }
        });
        
        // Función para mostrar confirmación antes de eliminar/archivar
        function mostrarConfirmacion(mensaje, callback) {
            if (confirm(mensaje)) {
                callback();
            }
        }
    </script>
    <script>views/js/responsive.js</script>
</body>
</html>

<?php
// Capturar contenido traducible
$content = ob_get_clean();

// Imprimir, traducido si lang='en'
if ($lang === 'en') {
    echo azureTranslate($content, 'en', 'es', true);
} else {
    echo $content;
}
?>