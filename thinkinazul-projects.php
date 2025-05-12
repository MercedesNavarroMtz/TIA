<?php
/*
Plugin Name: ThinkInAzul - Projects
Description: Plugin para gestionar una lista de proyectos con metadatos.
Version: 1.1
Author: CTN
*/

function crear_tabla_proyectos() {
    global $wpdb;
    $tabla_proyectos = $wpdb->prefix . 'proyectos'; // Prefijo dinámico para evitar conflictos
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $tabla_proyectos (
        id INT NOT NULL AUTO_INCREMENT,
        marca_temporal DATETIME NOT NULL,
        nombre_proyecto VARCHAR(255) NOT NULL,
        persona_contacto VARCHAR(255) NOT NULL,
        institucion VARCHAR(255) NOT NULL,
        direccion_postal VARCHAR(255) NOT NULL,
        comunidad_autonoma VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        palabras_clave VARCHAR(255) NOT NULL,
        resumen TEXT NOT NULL,
        linea_actuacion VARCHAR(255) NOT NULL,
        puntuacion VARCHAR(255) NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    importar_proyectos_csv(); // Llamamos a la función de importación
}
// Crear la tabla cuando el plugin se active
register_activation_hook(__FILE__, 'crear_tabla_proyectos');

// ===========================================================================================================


function importar_proyectos_csv() {
    if (get_option('proyectos_importados')) {
        return;
    }
    global $wpdb;
    $tabla_proyectos = $wpdb->prefix . 'proyectos';

    $archivo_csv = plugin_dir_path(__FILE__) . 'proyectos.csv';

    if (!file_exists($archivo_csv)) {
        error_log('Error: No se encontró el archivo CSV en ' . $archivo_csv);
        return;
    }

    $handle = fopen($archivo_csv, "r");

    if (!$handle) {
        error_log('Error: No se pudo abrir el archivo CSV.');
        return;
    }

    fgetcsv($handle, 1000, ","); // Saltar la primera fila (cabecera)

    while (($datos = fgetcsv($handle, 1000, ",", '"')) !== FALSE) {
        if (count($datos) !== 11) {
            error_log('Fila con datos incorrectos: ' . implode(", ", $datos));
            continue;
        }

        // Convertir la fecha al formato MySQL (YYYY-MM-DD HH:MM:SS)
        $fecha_formateada = null;
        $fecha_objeto = DateTime::createFromFormat('d/m/Y H:i:s', $datos[0]);
        if ($fecha_objeto) {
            $fecha_formateada = $fecha_objeto->format('Y-m-d H:i:s');
        } else {
            error_log("Error al convertir la fecha: " . $datos[0]);
            continue; // Omitir la fila si hay error en la fecha
        }

        $wpdb->insert(
            $tabla_proyectos,
            [
                'marca_temporal'   => $fecha_formateada, // Ahora la fecha es válida para MySQL
                'nombre_proyecto'  => sanitize_text_field($datos[1]),
                'persona_contacto' => sanitize_text_field($datos[2]),
                'institucion'      => sanitize_text_field($datos[3]),
                'direccion_postal' => sanitize_text_field($datos[4]),
                'comunidad_autonoma' => sanitize_text_field($datos[5]),
                'email'            => sanitize_email($datos[6]),
                'palabras_clave'   => sanitize_text_field($datos[7]),
                'resumen'          => sanitize_textarea_field($datos[8]),
                'linea_actuacion'  => sanitize_text_field($datos[9]),
                'puntuacion'       => sanitize_text_field($datos[10]),
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );
    }

    fclose($handle);


    update_option('proyectos_importados', true);

}

//=================================================================================================================================
function thinkinazul_proyectos_scripts() {
    // Enqueue vis.js from CDN
    wp_enqueue_script('vis-network', 'https://unpkg.com/vis-network/standalone/umd/vis-network.min.js', array('jquery'), null, true);
    wp_enqueue_style('vis-network-css', 'https://unpkg.com/vis-network/styles/vis-network.min.css');

    // Custom CSS for project display
    wp_enqueue_style('thinkinazul-proyectos-style', plugin_dir_url(__FILE__) . 'thinkinazul-proyectos.css');


}
add_action('wp_enqueue_scripts', 'thinkinazul_proyectos_scripts');


// =================================================================================================================================
function buscador_proyectos_shortcode() {
    // Get projects data for JavaScript
    global $wpdb;
    $tabla_proyectos = $wpdb->prefix . 'proyectos';
    $proyectos = $wpdb->get_results("SELECT  id, nombre_proyecto, palabras_clave, persona_contacto, institucion, direccion_postal, email, resumen, linea_actuacion, puntuacion, comunidad_autonoma FROM $tabla_proyectos ORDER BY id ASC");


    // Prepare data for JavaScript
    $proyectos_data = array();
    foreach ($proyectos as $index => $proyecto) {

        $titles_map = [
        'Ciencia de datos para la emergencia de inteligencia en el medio marino y litoral a través de la monitorización ambiental' => 'CIENCIADEDATOS',
        'OMEMAR' => 'OMEMAR',
        'CEAS' => 'CEAS',
        // Agrega aquí más proyectos según necesites
        ];

        $title_param = isset($titles_map[$proyecto->nombre_proyecto]) ? $titles_map[$proyecto->nombre_proyecto] : null;
        $ficha_url = $title_param ? "http://localhost/thinkin/index.php/resultados/radar-de-innovacion/?comunidad=all&linea=all&tematica=all&title=$title_param" : null;

        // Define la URL de la ficha según el nombre del proyecto
        // $ficha_url = null;
        // if($proyecto->nombre_proyecto == 'Ciencia de datos para la emergencia de inteligencia en el medio marino y litoral a través de la monitorización ambiental') {
        //     $ficha_url = 'http://localhost/thinkin/index.php/resultados/radar-de-innovacion/?comunidad=all&linea=all&tematica=all&title=CIENCIADEDATOS';
        // }

        $proyectos_data[] = array(
            'id' => $index,
            'nombre' => $proyecto->nombre_proyecto,
            'palabras_clave' => $proyecto->palabras_clave,
            'contacto' => $proyecto->persona_contacto,
            'institucion' => $proyecto->institucion,
            'direccion' => $proyecto->direccion_postal,
            'email' => $proyecto->email,
            'resumen' => $proyecto->resumen,
            'linea' => $proyecto->linea_actuacion,
            'puntuacion' => $proyecto->puntuacion,
            'comunidad' => $proyecto->comunidad_autonoma,
            'ficha' => $ficha_url
        );

    }
    $proyectos_json = json_encode($proyectos_data);

    ob_start();
    ?>
        <h2 class="bordered-title">LABORATORIO DE IDEAS</h2>

        <div id="buscador-proyectos">
            <!-- Contenedor de la tabla y el buscador -->
            <div class="panel-busqueda">
                <input type="text" id="busqueda" placeholder="Buscar proyectos..." onkeyup="buscarProyectos()">
                <select id="filtro_comunidad" onchange="buscarProyectos()">
                    <option value="">Todas las comunidades</option>
                </select>
                <div class="tabla-container">
                    <table id="tabla-proyectos">
                        <thead>
                            <tr>
                                <th>Nombre del Proyecto</th>
                                <th>CCAA</th>
                                <th>Palabras Clave</th>
                                <th>LA</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td colspan="3">Cargando proyectos...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Contenedor del gráfico -->
            <div id="grafico-container-buscador">
                <div id="network-container-buscador"></div>
            </div>

            <!-- Popup flotante -->
            <div id="popup-info" class="hidden">
                <p><strong>Persona de contacto:</strong> <span id="popup-contacto"></span></p>
                <p><strong>Institución:</strong> <span id="popup-institucion"></span></p>
                <p><strong>Dirección:</strong> <span id="popup-direccion"></span></p>
                <p><strong>Email:</strong> <span id="popup-email"></span></p>
                <p><strong>Resumen:</strong> <span id="popup-resumen"></span></p>
                <p><strong>Línea de actuación:</strong> <span id="popup-linea"></span></p>
                <p><strong>Puntuación:</strong> <span id="popup-puntuacion"></span></p>
                <p class="ficha-link">
                    <strong>Ficha de transferencia:</strong> <a id="popup-ficha" href="#" >Ver ficha</a>
                </p>
            </div>

        </div>
    
    <script>
        // Store projects data for the graph generation
        const proyectosData = <?php echo $proyectos_json; ?>;

        document.addEventListener("DOMContentLoaded", function() {
            cargarComunidades();

            const urlParams = new URLSearchParams(window.location.search);
            const buscarProyecto = urlParams.get("buscar");

            if (buscarProyecto) {
                document.getElementById("busqueda").value = buscarProyecto;

                // Esperamos a que se carguen las comunidades y luego lanzamos la búsqueda
                setTimeout(() => {
                    buscarProyectos();

                    // Esperamos a que cargue la tabla y entonces simulamos clic en el proyecto
                    const observer = new MutationObserver(function(mutations) {
                        const filas = document.querySelectorAll(".fila-proyecto");
                        filas.forEach(fila => {
                            const nombre = fila.querySelector("td:first-child").textContent.trim();
                            if (nombre.toLowerCase() === buscarProyecto.toLowerCase()) {
                                fila.click(); // Simula clic para abrir popup y resaltar en grafo
                            }
                        });
                        observer.disconnect(); // Solo queremos que se dispare una vez
                    });

                    const tableBody = document.querySelector("#tabla-proyectos tbody");
                    observer.observe(tableBody, { childList: true });
                }, 300); // Ajusta el timeout si hace falta
            } else {
                buscarProyectos();
                const tableBody = document.querySelector("#tabla-proyectos tbody");
                const observer = new MutationObserver(function(mutations) {
                    generarGrafoDesdeTablaCargada();
                });
                observer.observe(tableBody, { childList: true });
            }
        });
        
        //=======================================================================================
        function cargarComunidades() {
            fetch("<?php echo admin_url('admin-ajax.php?action=obtener_comunidades'); ?>")
                .then(response => response.json())
                .then(data => {
                    let select = document.getElementById("filtro_comunidad");
                    data.forEach(comunidad => {
                        let option = document.createElement("option");
                        option.value = comunidad;
                        option.textContent = comunidad;
                        select.appendChild(option);
                    });
                });
        }

        //===========================================================================================

        function buscarProyectos() {
            let termino = document.getElementById("busqueda").value;
            let comunidad = document.getElementById("filtro_comunidad").value;

            let formData = new FormData();
            formData.append("action", "buscar_proyectos");
            formData.append("termino", termino);
            formData.append("comunidad", comunidad);

            fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
                method: "POST",
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.querySelector("#tabla-proyectos tbody").innerHTML = data;
                asignarEventosPopup(); // Asignamos eventos después de cargar los datos
                generarGrafoDesdeTablaCargada();
            });
        }

        //===============================================================================
        function generarGrafoDesdeTablaCargada() {
            // Get the graph container
            const graficoContainer = document.getElementById("grafico-container-buscador");

            // Always show the graph container
            graficoContainer.style.display = "block";

            // Extract project names from the current table
            const projectNames = Array.from(
                document.querySelectorAll("#tabla-proyectos tbody tr td:nth-child(1)")
            ).map(td => td.textContent.trim());

            // Generate graph with current projects
            generateGraph(projectNames, proyectosData, "network-container-buscador");
        }
        //=================================================================================



        function asignarEventosPopup() {
            let filas = document.querySelectorAll(".fila-proyecto");
            let popup = document.getElementById("popup-info");
            let isPopupFixed = false;
            let currentHighlightedRow = null; // Track currently highlighted row

            // Limpiar cualquier evento previo para evitar duplicados
            filas.forEach(fila => {
                const newFila = fila.cloneNode(true);
                fila.parentNode.replaceChild(newFila, fila);
            });

            // ----------------------------------------------Función para posicionar el popup inteligentemente
            function posicionarPopup(event) {
                const popupHeight = popup.offsetHeight;
                const popupWidth = popup.offsetWidth;
                const padding = 15;

                let top, left;

                // Intenta colocarlo debajo del cursor
                const espacioAbajo = window.innerHeight - event.clientY;
                const espacioArriba = event.clientY;

                if (espacioAbajo >= popupHeight + padding) {
                    // Hay espacio suficiente debajo
                    top = event.clientY + padding;
                } else if (espacioArriba >= popupHeight + padding) {
                    // No hay espacio debajo pero sí arriba
                    top = event.clientY - popupHeight - padding;
                } else {
                    // Colócalo dentro del área visible lo mejor que puedas
                    top = Math.max(10, window.innerHeight - popupHeight - 10);
                }

                // Lógica para posicionamiento horizontal (derecha/izquierda del cursor)
                if (event.clientX + popupWidth + padding > window.innerWidth) {
                    left = event.clientX - popupWidth - padding;
                    if (left < 0) left = 10;
                } else {
                    left = event.clientX + padding;
                }

                popup.style.position = "fixed";
                popup.style.left = `${left}px`;
                popup.style.top = `${top}px`;
            }

            //----------------------------------------------------------------

            // Function to reset row highlighting -------------------------
            function resetRowHighlight() {
                if (currentHighlightedRow) {
                    currentHighlightedRow.style.backgroundColor = '';
                    currentHighlightedRow.style.border = '';
                    currentHighlightedRow = null;
                }
            }
            //------------------------------------------------------------------

            // Reasignar eventos a las nuevas filas
            filas = document.querySelectorAll(".fila-proyecto");
            filas.forEach(fila => {
                fila.addEventListener("click", function(event) {
                    event.stopPropagation();
                    
                    // Handle popup logic
                    if (isPopupFixed) {
                        const closeButton = document.getElementById("popup-close");
                        if (closeButton) {
                            closeButton.remove();
                        }

                        popup.style.border = "1px solid #ccc";
                        popup.style.backgroundColor = "rgba(255, 255, 255, 0.95)";
                        popup.style.display = "none";
                        isPopupFixed = false;
                        
                        // Reset row highlight when popup is closed
                        resetRowHighlight();
                        
                        // Reset graph node highlighting
                        if (window.projectNetwork) {
                            resetNodeHighlights();
                        }
                        } else {
                        document.getElementById("popup-contacto").textContent = fila.dataset.contacto;
                        document.getElementById("popup-institucion").textContent = fila.dataset.institucion;
                        document.getElementById("popup-direccion").textContent = fila.dataset.direccion;
                        document.getElementById("popup-email").textContent = fila.dataset.email;
                        document.getElementById("popup-resumen").textContent = fila.dataset.resumen;
                        document.getElementById("popup-linea").textContent = fila.dataset.linea;
                        document.getElementById("popup-puntuacion").textContent = fila.dataset.puntuacion;

                        const fichaLink = document.getElementById("popup-ficha");
                        if (fila.dataset.ficha) {
                            fichaLink.href = fila.dataset.ficha;
                            fichaLink.parentElement.style.display = "block";
                        } else {
                            fichaLink.href = "#";
                            fichaLink.parentElement.style.display = "none";
                        }

                        popup.style.display = "block";
                        posicionarPopup(event);

                        // Agregar botón de cierre
                        const closeButton = document.createElement("button");
                        closeButton.id = "popup-close";
                        closeButton.textContent = "×";
                        closeButton.style.position = "absolute";
                        closeButton.style.top = "5px";
                        closeButton.style.right = "5px";
                        closeButton.style.background = "#324093";
                        closeButton.style.color = "white";
                        closeButton.style.border = "none";
                        closeButton.style.borderRadius = "50%";
                        closeButton.style.width = "25px";
                        closeButton.style.height = "25px";
                        closeButton.style.cursor = "pointer";
                        
                        closeButton.addEventListener("click", function(e) {
                            e.stopPropagation();
                            popup.style.display = "none";
                            
                            const btn = document.getElementById("popup-close");
                            if (btn) btn.remove();

                            isPopupFixed = false;
                            
                            // Reset row highlight when X button is clicked
                            resetRowHighlight();
                            
                            // Reset graph node highlighting
                            if (window.projectNetwork) {
                                resetNodeHighlights();
                            }
                        });

                        popup.appendChild(closeButton);

                        popup.style.border = "2px solid #324093";
                        popup.style.backgroundColor = "rgba(255, 255, 255, 1)";
                        isPopupFixed = true;
                    }
                    
                    // Get project name from the first cell
                    const projectName = fila.querySelector('td:first-child').textContent.trim();
                    
                    // Reset any previously highlighted row
                    resetRowHighlight();
                    
                    // Highlight the row and store reference
                    fila.style.backgroundColor = 'rgb(255, 197, 197)';
                    fila.style.border = '2px solid #FF5733';
                    currentHighlightedRow = fila;
                    
                    // Focus on the node and highlight it
                    if (window.projectNetwork) {
                        window.projectNetwork.focus(projectName, {
                            scale: 1.3,
                            animation: true
                        });
                        highlightNode(projectName);

                    }

                });
            });
            
            // Keep the global click listener to close the popup when clicking elsewhere
            document.addEventListener("click", function(event) {
                if (!popup.contains(event.target) && isPopupFixed) {
                    const closeButton = document.getElementById("popup-close");
                    if (closeButton) closeButton.click();
                    
                    // This will reset the row highlight since we're calling the closeButton's click event
                    // which includes the resetRowHighlight() function
                }
            });
        }
        //========================================================================
        // Modificar la función buscarProyectos para llamar a asignarEventosPopup después de cargar los datos
        function buscarProyectos() {
            let termino = document.getElementById("busqueda").value;
            let comunidad = document.getElementById("filtro_comunidad").value;

            let formData = new FormData();
            formData.append("action", "buscar_proyectos");
            formData.append("termino", termino);
            formData.append("comunidad", comunidad);

            fetch("<?php echo admin_url('admin-ajax.php'); ?>", {
                method: "POST",
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                document.querySelector("#tabla-proyectos tbody").innerHTML = data;
                asignarEventosPopup(); // Reasignar eventos después de cargar los datos
                generarGrafoDesdeTablaCargada();
            });
        }
        //========================================================================

        function generateGraph(selectedProjects, allProjectsData, containerId) {
            // Verificar que el contenedor existe
            const container = document.getElementById(containerId);
            if (!container) {
                console.error(`El contenedor con ID ${containerId} no existe en el DOM`);
                return null;
            }

            container.innerHTML = "";

            // Verificar que tenemos datos
            if (!allProjectsData || allProjectsData.length === 0) {
                console.error("No hay datos de proyectos disponibles");
                container.innerHTML = "<p>No se pudieron cargar los datos de proyectos</p>";
                return null;
            }

            console.log("project: ", allProjectsData[0]);
            // Filter projects based on selected project names
            const filteredProjects = allProjectsData.filter(project => 
                selectedProjects.includes(project.nombre)
            );
            console.log("Proyectos filtrados: ", filteredProjects);

            // Si no hay proyectos filtrados, mostrar mensaje
            if (filteredProjects.length === 0) {
                console.error("No hay proyectos seleccionados");
                container.innerHTML = "<p>Por favor seleccione al menos un proyecto</p>";
                return null;
            }

            // Collect keywords and project names
            let allKeywords = [];
            let projectNames = [];

            filteredProjects.forEach(project => {
                console.log("proyecto: ", project);
                // Verificar que el proyecto tiene nombre y palabras clave
                if (!project.nombre || !project.palabras_clave) {
                    console.warn("Proyecto sin nombre o palabras clave:", project);
                    return; // Saltar este proyecto
                }

                // Add project name
                projectNames.push(project.nombre);

                // Process keywords (split by comma and normalize)
                const keywords = project.palabras_clave.split(',').map(kw => {
                    // Remove accents and normalize 
                    return kw.trim().toLowerCase()
                        .normalize('NFD').replace(/[\u0300-\u036f]/g, ''); // Remove accents
                });

                // Add to all keywords
                allKeywords = allKeywords.concat(keywords);
            });

            // Get unique keywords and projects
            const uniqueKeywords = [...new Set(allKeywords)];
            const uniqueProjects = [...new Set(projectNames)];

            // Verificar que tenemos nodos para crear
            if (uniqueProjects.length === 0 || uniqueKeywords.length === 0) {
                console.error("No hay suficientes datos para crear el grafo");
                container.innerHTML = "<p>No hay suficientes datos para crear el grafo</p>";
                return null;
            }

            // Verificar que vis.js está cargado
            if (typeof vis === 'undefined') {
                console.error("La librería vis.js no está cargada");
                container.innerHTML = "<p>Error: Librería de visualización no disponible</p>";
                return null;
            }

            // Create nodes for projects (blue) and keywords (black)
            const nodes = new vis.DataSet();

            // Add project nodes
            uniqueProjects.forEach(project => {
                nodes.add({
                    id: project,
                    label: project.replace(/(.{1,20})(\s+|$)/g, "$1\n").trim(),
                    color: '#324093', // Blue color for projects
                    font: { color: '#000000', size: 12 },
                    size: 25
                });
            });

            // Add keyword nodes
            uniqueKeywords.forEach(keyword => {
                nodes.add({
                    id: keyword,
                    label: keyword,
                    color: '#000000', // Black color for keywords
                    font: { color: '#000000', size: 10 },
                    size: 15
                });
            });

            // Create edges (connections between projects and keywords)
            const edges = new vis.DataSet();

            // For each project, connect to its keywords
            filteredProjects.forEach(project => {
                const projectName = project.nombre;

                // Process keywords and create edges
                const keywords = project.palabras_clave.split(',').map(kw =>
                    kw.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                );

                keywords.forEach(keyword => {
                    edges.add({
                        from: projectName,
                        to: keyword,
                        color: { color: '#888888', opacity: 0.7 }
                    });
                });
            });

            // Create a network
            const data = {
                nodes: nodes,
                edges: edges
            };

            const options = {
                nodes: {
                    shape: 'dot',
                    scaling: {
                        min: 10,
                        max: 30
                    }
                },
                edges: {
                    width: 1.5,
                    smooth: {
                        type: 'dynamic'
                    }
                },
                physics: {
                    forceAtlas2Based: {
                        gravitationalConstant: -26,
                        centralGravity: 0.005,
                        springLength: 230,
                        springConstant: 0.18
                    },
                    maxVelocity: 146,
                    solver: 'forceAtlas2Based',
                    timestep: 0.35,
                    stabilization: { iterations: 150 }
                },
                interaction: {
                    hover: true,
                    navigationButtons: true,
                    zoomView: true
                }
            };

            const network = new vis.Network(container, data, options);

            window.projectNetwork = network;

            let selectedNodeId = null;

            // Definir resetTableHighlights que faltaba
            function resetTableHighlights() {
                const rows = document.querySelectorAll("#tabla-proyectos tbody tr.fila-proyecto");
                rows.forEach(row => {
                    row.classList.remove("highlighted-row");
                    row.style.backgroundColor = '';
                    row.style.border = '';
                    row.querySelector('td:first-child').style.fontWeight = '';
                });
            }

            network.on("selectNode", function(params) {
                if (params.nodes.length > 0) {
                    const nodeId = params.nodes[0];

                    if (uniqueProjects.includes(nodeId)) {
                        const relacionados = getRelatedProjects(nodeId, allProjectsData);

                        document.getElementById("busqueda").value = '';
                        document.getElementById("filtro_comunidad").selectedIndex = 0;

                        const tableBody = document.querySelector("#tabla-proyectos tbody");
                        tableBody.innerHTML = ''; // limpiar

                        allProjectsData.forEach(p => {
                            if (relacionados.includes(p.nombre)) {
                                const fila = document.createElement("tr");
                                fila.classList.add("fila-proyecto");
                                fila.setAttribute("data-contacto", p.contacto);
                                fila.setAttribute("data-institucion", p.institucion);
                                fila.setAttribute("data-direccion", p.direccion);
                                fila.setAttribute("data-email", p.email);
                                fila.setAttribute("data-resumen", p.resumen);
                                fila.setAttribute("data-linea", p.linea);
                                fila.setAttribute("data-puntuacion", p.puntuacion);
                                fila.setAttribute("data-ficha", p.ficha || '');

                                fila.innerHTML = `
                                    <td>${p.nombre}</td>
                                    <td>${p.comunidad}</td>
                                    <td>${p.palabras_clave}</td>
                                    <td>${p.linea}</td>
                                `;

                                tableBody.appendChild(fila);
                            }
                        });

                        asignarEventosPopup();
                        generateGraph(relacionados, allProjectsData, "network-container-buscador");

                        selectedNodeId = nodeId;
                    }
                }
            });


                    

            function unhighlightNode(nodeId) {
                network.body.data.nodes.update({ id: nodeId, color: undefined });
            }
            
            function unhighlightTableRow(projectName) {
                const cell = Array.from(
                    document.querySelectorAll("#tabla-proyectos tbody tr.fila-proyecto td:first-child")
                ).find(cell => cell.textContent.trim() === projectName);
                
                if (cell) {
                    const row = cell.parentNode;
                    row.classList.remove("highlighted-row");
                    row.style.backgroundColor = '';
                    row.style.border = '';
                    cell.style.fontWeight = '';
                }
            }

            function highlightTableRow(projectName) {
                const cell = Array.from(
                    document.querySelectorAll("#tabla-proyectos tbody tr.fila-proyecto td:first-child")
                ).find(cell => cell.textContent.trim() === projectName);
                
                if (cell) {
                    const row = cell.parentNode;
                    
                    // Aplicar estilos de resaltado
                    row.classList.add("highlighted-row");
                    row.style.backgroundColor = '#f8f9fa';
                    row.style.border = '2px solid rgb(252, 62, 62)';
                    cell.style.fontWeight = 'bold';
                    
                    // Obtener el contenedor de la tabla que tiene scroll
                    // Podría ser la propia tabla o un div que la contiene
                    const tableContainer = document.getElementById("tabla-proyectos").closest('.table-container') || 
                                        document.getElementById("tabla-proyectos").parentElement;
                    
                    if (tableContainer) {
                        // Calcular la posición de la fila respecto al contenedor
                        const rowRect = row.getBoundingClientRect();
                        const containerRect = tableContainer.getBoundingClientRect();
                        
                        // Calcular dónde debe estar el scroll para que la fila sea visible
                        // Restamos un poco para que no quede exactamente al borde
                        const scrollTop = row.offsetTop - tableContainer.offsetTop - (containerRect.height / 4);
                        
                        // Desplazar suavemente al punto calculado
                        tableContainer.scrollTo({
                            top: scrollTop,
                            behavior: 'smooth'
                        });
                    }
                }
            }

            function highlightNode(nodeId) { 
                // Resetear resaltados previos
                resetHighlights();
                
                // Obtener todos los nodos conectados (palabras clave para este proyecto)
                const connectedNodes = network.getConnectedNodes(nodeId);
                
                // Resaltar el nodo con TU COLOR PREFERIDO
                nodes.update({
                    id: nodeId,
                    color: 'rgb(252, 62, 62)', // Cambia este color al que prefieras (rojo en este ejemplo)
                    // size: 30
                });
                
                // Resaltar aristas y nodos conectados
                for (let i = 0; i < connectedNodes.length; i++) {
                    const connectedNodeId = connectedNodes[i];
                    
                    // Resaltar nodo conectado
                    nodes.update({
                        id: connectedNodeId,
                        color: 'rgb(253, 198, 115)', // Puedes cambiar este color también para las palabras clave conectadas
                        // size: 20
                    });
                }
            }

            function resetHighlights() {
                // Reset all nodes to their original colors
                nodes.forEach(node => {
                    if (node.id && typeof node.id === 'string') {
                        // Check if it's a project or keyword node
                        if (projectNames.includes(node.id)) {
                            nodes.update({
                                id: node.id,
                                color: '#324093', // Original project color
                                size: 25
                            });
                        } else {
                            nodes.update({
                                id: node.id,
                                color: '#000000', // Original keyword color
                                size: 15
                            });
                        }
                    }
                });
                
                // Reset all edges
                // edges.forEach(edge => {
                //     edges.update({
                //         id: edge.id,
                //         color: { color: '#888888', opacity: 0.7 },
                //         width: 1.5
                //     });
                // });
                
                // Remove highlighting from table rows
                resetTableHighlights();
                
                // highlightActive = false;  // Variable no utilizada, comentada
                // highlightedNode = null;   // Variable no utilizada, comentada
            }

            // Adjust the layout when the graph is stable
            network.on('stabilizationIterationsDone', function() {
                network.setOptions({ physics: false });
            });
            if (window.projectNetwork) {
                // Cierra el popup al hacer clic en cualquier parte del grafo
                window.projectNetwork.on("click", function (params) {
                    const popup = document.getElementById("popup-info");
                    const closeButton = document.getElementById("popup-close");

                    if (popup && closeButton) {
                        closeButton.click();
                    }
                });
            }
            
            return network;
        }
        function getRelatedProjects(projectName, allProjectsData) {
            const targetProject = allProjectsData.find(p => p.nombre === projectName);
            if (!targetProject) return [];

            const targetKeywords = targetProject.palabras_clave.split(',').map(kw =>
                kw.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
            );

            const relatedProjects = allProjectsData.filter(p => {
                if (p.nombre === projectName) return true; // incluir el propio
                const keywords = p.palabras_clave.split(',').map(kw =>
                    kw.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                );
                return keywords.some(kw => targetKeywords.includes(kw));
            });

            return relatedProjects.map(p => p.nombre);
        }

        //==============================================================================
        // FUNCION PARA BUSCAR LA FILA Y SCROLEAR HASTA ELLA - FUNCION QUE VA LENTA CREO
        let isSearchingRow = false;

        // Función optimizada para buscar y resaltar filas
        function highlightTableRow(projectName) {
            // Si ya hay una búsqueda en progreso, cancelarla
            if (isSearchingRow) return;
            
            // Activar estado de búsqueda
            isSearchingRow = true;
            
            // Mostrar indicador de carga
            showLoadingIndicator();
            document.body.offsetHeight; // Forzar reflow para asegurar render antes del timeout

            
            // Usar setTimeout para dar tiempo al navegador a mostrar el indicador de carga
            setTimeout(() => {
                try {
                    // Resetear resaltados previos usando una única operación de clase
                    const previouslyHighlighted = document.querySelector("#tabla-proyectos tbody tr.highlighted-row");
                    if (previouslyHighlighted) {
                        previouslyHighlighted.classList.remove("highlighted-row");
                        previouslyHighlighted.style.backgroundColor = '';
                        previouslyHighlighted.style.border = '';
                    }
                    
                    // Optimización: usar querySelector en lugar de querySelectorAll
                    // Esto es más rápido porque se detiene en la primera coincidencia
                    const foundCell = Array.from(
                        document.querySelectorAll("#tabla-proyectos tbody tr.fila-proyecto td:first-child")
                    ).find(cell => cell.textContent.trim() === projectName);
                    
                    // Si encontramos la celda, procesar su fila
                    if (foundCell) {
                        const foundRow = foundCell.parentNode;
                        
                        // Usar classList para añadir la clase
                        foundRow.classList.add("highlighted-row");
                        foundRow.style.backgroundColor = 'rgb(255, 197, 197)';
                        foundRow.style.border = '2px solid #FF5733';
                        
                        // Obtener el contenedor de la tabla (donde queremos hacer scroll)
                        const tableContainer = document.querySelector("#tabla-proyectos").closest('.table-container') || 
                                            document.querySelector("#tabla-proyectos").parentElement;
                        
                        // Calcular la posición de la fila respecto al contenedor
                        const rowRect = foundRow.getBoundingClientRect();
                        const containerRect = tableContainer.getBoundingClientRect();
                        
                        // Calcular la posición a la que hacer scroll
                        const scrollTop = tableContainer.scrollTop + (rowRect.top - containerRect.top) - 
                                        (containerRect.height / 2) + (rowRect.height / 2);
                        
                        // Hacer scroll en el contenedor de la tabla, no en toda la página
                        tableContainer.scrollTop = scrollTop;
                    }
                } finally {
                    // Ocultar indicador de carga
                    hideLoadingIndicator();
                    
                    // Desactivar estado de búsqueda
                    isSearchingRow = false;
                }
            }, 100); // Un pequeño retraso para permitir que el indicador de carga se muestre
        }
        //=============================================================

        // // Función para mostrar indicador de carga
        // function showLoadingIndicator() {
        //     const loader = document.getElementById('loading-indicator');
        //     if (loader) {
        //         loader.style.display = 'block';
        //     }
        // }

        // function hideLoadingIndicator() {
        //     const loader = document.getElementById('loading-indicator');
        //     if (loader) {
        //         loader.style.display = 'none';
        //     }
        // }

        //=============================================================
        // Función para resetear los nodos destacados en el grafo
        function resetNodeHighlights() {
            if (window.projectNetwork) {
                const nodes = window.projectNetwork.body.data.nodes;
                const edges = window.projectNetwork.body.data.edges;
                
                // Reset all nodes to their original colors
                nodes.forEach(node => {
                    if (node.id && typeof node.id === 'string') {
                        // Check if it's a project or keyword node
                        const isProjectNode = projectNames.includes(node.id);
                        nodes.update({
                            id: node.id,
                            color: isProjectNode ? ' #324093' : ' #000000', // Original colors
                            size: isProjectNode ? 25 : 15
                        });
                    }
                });
                
                // Reset all edges
                edges.forEach(edge => {
                    edges.update({
                        id: edge.id,
                        color: { color: ' #888888', opacity: 0.7 },
                        width: 1.5
                    });
                });
            }
        }
        //====================================================================

        // Integrar la nueva función a la lógica de reseteo existente
        function resetRowHighlight() {
            const rows = document.querySelectorAll('#tabla-proyectos tbody tr');
            rows.forEach(row => {
                row.style.backgroundColor = '';
                row.style.border = '';
            });
            
            // También ocultar el popup
            const popup = document.getElementById("popup-info");
            if (popup) {
                popup.style.display = "none";
            }
        }
        //=================================================================
        // Function to reset table highlighting
        function resetTableHighlights() {
            const rows = document.querySelectorAll('#tabla-proyectos tbody tr');
            rows.forEach(row => {
                row.style.backgroundColor = '';
                row.style.border = '';
            });
        }



    </script>
    <style>
        #tabla-proyectos {
            width: 100%;
            border-collapse: collapse;
        }
        #tabla-proyectos td, #tabla-proyectos th {
            border: 1px solid rgb(208, 187, 149);
            padding: 8px;
        }

        #tabla-proyectos tr:hover {background-color: #ddd;}

        th {
            text-align: left;
            background-color: #324093;
            color: white;
            font-family: "Gotham", Sans-serif;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 2rem;
        }

        #buscador-proyectos {
            display: flex;
            /* justify-content: space-between;
            align-items: flex-start; */
            width: 100%; 
            max-width: 100%;
            margin: 0 auto;
            box-sizing: border-box;
            gap: 40px;
            padding: 0 15px;
            margin-left: -20px; /* Ajusta el valor según lo necesites */

        }

        .panel-busqueda {
            flex: 3;
            width: 60%;
            min-width: 500px;
            max-width: 1000px;
            box-sizing: border-box;
            margin-top: 45px; /* Ajusta el valor según lo necesites */

        }
        .tabla-container {
            max-height: 1115px;
            overflow-y: auto;
        }

        #tabla-proyectos thead th {
            position: sticky;
            top: 0;
            z-index: 1;
        }


        .bordered-title {
            font-size: 2.5rem;
            color: #324093;
            border: 5px solid #324093;
            /* display: inline-block; */
            padding: 10px 15px;
            margin-bottom: 20px;
            background: transparent;
            box-shadow: 4px 4px 0px rgba(50, 64, 147, 0.9);
            font-size: 50px;
            /* text-align: right; */
            margin-left: 50px;
            margin-right: 50px;
            width: 580px;
            /* margin-top:50px; */
        }

        #popup-info {
            position: fixed; /* Cambiar de absolute a fixed */
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #ccc;
            padding: 10px;
            box-shadow: 2px 2px 10px rgba(0,0,0,0.2);
            display: none;
            max-width: 250px;
            z-index: 1000;
            font-size: 14px;
            max-width: 300px; /* Slightly wider */
            word-wrap: break-word; /* Ensure long text doesn't overflow */
            user-select: text;
        }

        .hidden {
            display: none;
        }

        #grafico-container-buscador {
            flex: 2;
            width: 40%;
            min-width: 300px;
            max-width: 700px;
            box-sizing: border-box;
            margin-left: -20px;

        }

        #network-container-buscador {
            width: 800px; 
            height: 700px;
            border: 1px solid #ccc;
            margin-top: 75px; /* Ajusta el valor según lo necesites */

        }

        /* Responsive Design */
        @media screen and (max-width: 1024px) {
            #buscador-proyectos {
                flex-direction: column;
            }
            
            .panel-busqueda, 
            #grafico-container-buscador {
                width: 100%;
                min-width: auto;
            }
        }

    </style>

    
    <?php
    return ob_get_clean();
}


//========================================================================================================
add_shortcode('buscador_proyectos', 'buscador_proyectos_shortcode');



// function limpiar_proyectos_duplicados() {
//     global $wpdb;
//     $tabla_proyectos = $wpdb->prefix . 'proyectos';
    
//     // Esta consulta mantiene solo la entrada con el ID más bajo para cada proyecto duplicado
//     $query = "
//         DELETE t1 FROM $tabla_proyectos t1
//         INNER JOIN $tabla_proyectos t2 
//         WHERE 
//             t1.id > t2.id AND 
//             t1.nombre_proyecto = t2.nombre_proyecto AND
//             t1.persona_contacto = t2.persona_contacto AND
//             t1.institucion = t2.institucion
//     ";
    
//     $wpdb->query($query);
// }

// Puedes ejecutar esta función manualmente una vez desde un punto seguro
// add_action('init', 'limpiar_proyectos_duplicados');


//===================================================================================================================================================

function buscar_proyectos_ajax() {
    global $wpdb;
    $tabla_proyectos = $wpdb->prefix . 'proyectos';

    $termino = isset($_POST['termino']) ? sanitize_text_field($_POST['termino']) : '';
    $comunidad = isset($_POST['comunidad']) ? sanitize_text_field($_POST['comunidad']) : '';

    $query = "SELECT * FROM $tabla_proyectos WHERE 1=1";

    if (!empty($termino)) {
        $query .= " AND (nombre_proyecto LIKE %s OR persona_contacto LIKE %s OR palabras_clave LIKE %s)";
    }

    if (!empty($comunidad)) {
        $query .= " AND comunidad_autonoma = %s";
    }

    $query .= " ORDER BY id";

    $params = [];
    if (!empty($termino)) {
        $busqueda = '%' . $wpdb->esc_like($termino) . '%';
        array_push($params, $busqueda, $busqueda, $busqueda);
    }

    if (!empty($comunidad)) {
        array_push($params, $comunidad);
    }

    $resultados = empty($params) ? $wpdb->get_results($query) : $wpdb->get_results($wpdb->prepare($query, ...$params));

    if ($resultados) {
        foreach ($resultados as $index => $proyecto) {
            // Extraer el número de línea de actuación
            $linea_numero = '-';
            if (preg_match('/Línea de Actuación\s*(\d+):/i', $proyecto->linea_actuacion, $matches)) {
                $linea_numero = $matches[1];
            }

            $titles_map = [
                'Ciencia de datos para la emergencia de inteligencia en el medio marino y litoral a través de la monitorización ambiental' => 'CIENCIADEDATOS',
                'OMEMAR' => 'OMEMAR',
                'CEAS' => 'CEAS',
                // Añade más si hace falta
            ];

            $title_param = isset($titles_map[$proyecto->nombre_proyecto]) ? $titles_map[$proyecto->nombre_proyecto] : null;
            $ficha_url = $title_param ? "http://localhost/thinkin/index.php/resultados/radar-de-innovacion/?comunidad=all&linea=all&tematica=all&title={$title_param}" : '';

            

            echo '<tr class="fila-proyecto" 
                    data-contacto="' . esc_attr($proyecto->persona_contacto) . '" 
                    data-institucion="' . esc_attr($proyecto->institucion) . '" 
                    data-direccion="' . esc_attr($proyecto->direccion_postal) . '" 
                    data-email="' . esc_attr($proyecto->email) . '" 
                    data-resumen="' . esc_attr($proyecto->resumen) . '" 
                    data-linea="' . esc_attr($proyecto->linea_actuacion) . '" 
                    data-puntuacion="' . esc_attr($proyecto->puntuacion) . '"
                    data-ficha="' . esc_url($ficha_url) . '">
                <td>' . esc_html($proyecto->nombre_proyecto) . '</td>
                <td>' . esc_html($proyecto->comunidad_autonoma) . '</td>
                <td>' . esc_html($proyecto->palabras_clave) . '</td>
                <td>' . esc_html($proyecto->linea_actuacion) . '</td>
            </tr>';
        }
    } else {
        echo "<tr><td colspan='4'>No se encontraron proyectos.</td></tr>";
    }

    wp_die();
}

add_action('wp_ajax_buscar_proyectos', 'buscar_proyectos_ajax');
add_action('wp_ajax_nopriv_buscar_proyectos', 'buscar_proyectos_ajax');


//=================================================================================================================================
//En esta función es donde obtiene los filtros de la tabla

function obtener_comunidades_ajax() {
    global $wpdb;
    $tabla_proyectos = $wpdb->prefix . 'proyectos';

    $comunidades = $wpdb->get_col("SELECT DISTINCT comunidad_autonoma FROM $tabla_proyectos WHERE comunidad_autonoma != '' ORDER BY comunidad_autonoma ASC");

    wp_send_json($comunidades);
}
add_action('wp_ajax_obtener_comunidades', 'obtener_comunidades_ajax');
add_action('wp_ajax_nopriv_obtener_comunidades', 'obtener_comunidades_ajax');


// =================================================================================================================================

function create_css_file() {
    $css_content = "
    #tabla-proyectos {
        border-collapse: collapse;
        width: 100%;
    }
    #tabla-proyectos td, #tabla-proyectos th {
        border: 1px solid rgb(208, 187, 149);
        padding: 8px;
    }
    #tabla-proyectos tr:nth-child(even) {
        background-color: #edd9b5;
    }
    #tabla-proyectos tr:hover {
        background-color: #ddd;
    }
    .network-container {
        width: 100%;
        height: 800px;
        border: 1px solid #ccc;
    }
    ";

    $css_file = plugin_dir_path(__FILE__) . 'thinkinazul-proyectos.css';
    file_put_contents($css_file, $css_content);
}
register_activation_hook(__FILE__, 'create_css_file');

