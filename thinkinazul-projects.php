<?php
/*
Plugin Name: ThinkInAzul - Projects
Description: Plugin para gestionar una lista de proyectos con metadatos.
Version: 1.1
Author: CTN
*/

function crear_tabla_proyectos() {
    global $wpdb; # Objeto de acceso a la base de datos
    $tabla_proyectos = $wpdb->prefix . 'proyectos'; // Crea nombre de la tabla
    $charset_collate = $wpdb->get_charset_collate(); // Obtener los caracteres utfmb4 y tal, compatibilidad

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
    ) $charset_collate;"; // SQL que creea la tabla

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql); // ejecuta el comando sql, si no existe la tabla la crea y si existe y es diferente la actualiza

    importar_proyectos_csv(); // Llamamos a la función de importación
}
// Crear la tabla cuando el plugin se active
register_activation_hook(__FILE__, 'crear_tabla_proyectos');

// ===========================================================================================================
// function insertar_proyecto_manual($datos) {
//     global $wpdb;
//     $tabla = $wpdb->prefix . 'proyectos';

//     // Verifica que estén los campos mínimos necesarios
//     if (empty($datos['nombre_proyecto']) || empty($datos['marca_temporal'])) {
//         return false;
//     }

//     // Convertir fecha al formato MySQL si es necesario
//     $fecha_obj = DateTime::createFromFormat('d/m/Y H:i:s', $datos['marca_temporal']);
//     if (!$fecha_obj) return false;

//     $fecha_mysql = $fecha_obj->format('Y-m-d H:i:s');

//     // Comprobar si ya existe
//     $existe = $wpdb->get_var($wpdb->prepare(
//         "SELECT COUNT(*) FROM $tabla WHERE nombre_proyecto = %s AND marca_temporal = %s",
//         $datos['nombre_proyecto'], $fecha_mysql
//     ));

//     if ($existe) return false;

//     // Insertar
//     return $wpdb->insert($tabla, [
//         'marca_temporal'     => $fecha_mysql,
//         'nombre_proyecto'    => sanitize_text_field($datos['nombre_proyecto']),
//         'persona_contacto'   => sanitize_text_field($datos['persona_contacto']),
//         'institucion'        => sanitize_text_field($datos['institucion']),
//         'direccion_postal'   => sanitize_text_field($datos['direccion_postal']),
//         'comunidad_autonoma' => sanitize_text_field($datos['comunidad_autonoma']),
//         'email'              => sanitize_email($datos['email']),
//         'palabras_clave'     => sanitize_text_field($datos['palabras_clave']),
//         'resumen'            => sanitize_textarea_field($datos['resumen']),
//         'linea_actuacion'    => sanitize_text_field($datos['linea_actuacion']),
//         'puntuacion'         => sanitize_text_field($datos['puntuacion']),
//     ]);
// }
// //====================================================================

// add_action('init', function () {
//     if (isset($_GET['insertar_nuevo'])) {
//         $nuevo = [
//             'marca_temporal'     => '13/05/2025 13:32:00',
//             'nombre_proyecto'    => 'Clasificador de Eventos Acústicos Submarinos',
//             'persona_contacto'   => 'Rosa Martínez Álvarez-Castellanos',
//             'institucion'        => 'Centro Tecnológico Naval y del Mar',
//             'direccion_postal'   => 'Ctra. El Estrecho-Lobosillo Fuente Álamo',
//             'comunidad_autonoma' => 'Región de Murcia',
//             'email'              => 'rosamartinez@ctnaval.com',
//             'palabras_clave'     => 'ciencia de datos, clasificación, modelo, observación, IA, acústica, conservación',
//             'resumen'            => 'Herramienta avanzada basada en inteligencia artificial (IA) que identifica y clasifica eventos acústicos submarinos, facilitando el monitoreo y la gestión de áreas marinas protegidas. Permite anticipar problemas, optimizar la respuesta y tomar decisiones informadas respaldadas por datos precisos. Gracias a la integración de GPUs de última generación, ofrece un rendimiento superior, acelerando el procesado de datos y modelos de IA. Este clasificador acústico transforma lo desconocido del entorno submarino en una oportunidad para proteger y gestionar ecosistemas clave.',
//             'linea_actuacion'    => 'Línea de Actuación 1: Observación y Monitorización del medio marino y litoral',
//             'puntuacion'         => '0',
//         ];
//         insertar_proyecto_manual($nuevo);
//         exit(' Proyecto insertado');
//     }
// });

//Ejecuto localhost/thinkin/?insertar_nuevo=1 para insertar el proyecto manualmente.
//============================================================================

function importar_proyectos_csv() {
    global $wpdb;
    $tabla_proyectos = $wpdb->prefix . 'proyectos';

    $archivo_csv = plugin_dir_path(__FILE__) . 'proyectos.csv';

    if (!file_exists($archivo_csv)) {
        error_log(' No se encontró el archivo CSV en ' . $archivo_csv);
        return;
    }

    $handle = fopen($archivo_csv, "r");
    if (!$handle) {
        error_log(' No se pudo abrir el archivo CSV.');
        return;
    }

    // Saltar la cabecera
    fgetcsv($handle, 1000, ",");

    while (($datos = fgetcsv($handle, 1000, ",", '"')) !== FALSE) {
        if (count($datos) !== 11) {
            error_log('Fila con datos incorrectos: ' . implode(", ", $datos));
            continue;
        }

        // Convertir fecha a formato MySQL
        $fecha_formateada = null;
        $fecha_objeto = DateTime::createFromFormat('d/m/Y H:i:s', $datos[0]);
        if ($fecha_objeto) {
            $fecha_formateada = $fecha_objeto->format('Y-m-d H:i:s');
        } else {
            error_log(" Error al convertir la fecha: " . $datos[0]);
            continue;
        }

        // Verificar si ya existe este proyecto (por nombre + fecha)
        $existe = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $tabla_proyectos WHERE nombre_proyecto = %s AND marca_temporal = %s",
            $datos[1], $fecha_formateada
        ));

        if ($existe) {
            continue; 
        }

        // Insertar nuevo proyecto
        $wpdb->insert(
            $tabla_proyectos,
            [
                'marca_temporal'     => $fecha_formateada,
                'nombre_proyecto'    => sanitize_text_field($datos[1]),
                'persona_contacto'   => sanitize_text_field($datos[2]),
                'institucion'        => sanitize_text_field($datos[3]),
                'direccion_postal'   => sanitize_text_field($datos[4]),
                'comunidad_autonoma' => sanitize_text_field($datos[5]),
                'email'              => sanitize_email($datos[6]),
                'palabras_clave'     => sanitize_text_field($datos[7]),
                'resumen'            => sanitize_textarea_field($datos[8]),
                'linea_actuacion'    => sanitize_text_field($datos[9]),
                'puntuacion'         => sanitize_text_field($datos[10]),
            ],
            ['%s','%s','%s','%s','%s','%s','%s','%s','%s','%s','%s']
        );
    }

    fclose($handle);
    error_log(' Importación de proyectos completada.');
}



//=================================================================================================================================
function thinkinazul_proyectos_scripts() { //estilos y js
    // Enqueue vis.js from CDN
    wp_enqueue_script('vis-network', 'https://unpkg.com/vis-network/standalone/umd/vis-network.min.js', array('jquery'), null, true);
    wp_enqueue_style('vis-network-css', 'https://unpkg.com/vis-network/styles/vis-network.min.css');

    // Custom CSS for project display
    wp_enqueue_style('thinkinazul-proyectos-style', plugin_dir_url(__FILE__) . 'thinkinazul-proyectos.css');


}
add_action('wp_enqueue_scripts', 'thinkinazul_proyectos_scripts');



// =================================================================================================================================
function buscador_proyectos_shortcode() {
    global $wpdb;
    $tabla_proyectos = $wpdb->prefix . 'proyectos';
    $proyectos = $wpdb->get_results("SELECT  id, nombre_proyecto, palabras_clave, persona_contacto, institucion, direccion_postal, email, resumen, linea_actuacion, puntuacion, comunidad_autonoma FROM $tabla_proyectos ORDER BY id ASC");
    // obtenemos la lista completa de los proyectos , consulta bbdd

    $proyectos_data = array();
    foreach ($proyectos as $index => $proyecto) { //recorre todos los resultados de la consulta anterior 

        $titles_map = [ //mapa que relaciona el titulo del proyecto con la ficha por su title
        'Ciencia de datos para la emergencia de inteligencia en el medio marino y litoral a través de la monitorización ambiental' => 'CIENCIADEDATOS',
        'OMEMAR' => 'OMEMAR',
        'Clasificador de Eventos Acústicos Submarinos' => 'CEAS',
        'OMM-Azul' => 'OMMazul',
        'Observatorio de la Gobernanza Marina (OGMAR)'=> 'OGMAR',
        'UEM-IEO-CSIC' =>'CSIC',
        'Monitorización Marina Mediante Equipos de AUVs Colaborativos' =>'AUVCOLLAB',
        'Mamíferos marinos como inidcadores de riesgos por contaminantes ambientales emergentes en las costas de la Región de Murcia (MARFARISK)'=>'MARFARISK',


        
        ];


        $title_param = isset($titles_map[$proyecto->nombre_proyecto]) ? $titles_map[$proyecto->nombre_proyecto] : null; //generamos la url a la que luego ira so coinciden los títulos 
        $ficha_url = $title_param ? add_query_arg(
            array(
                'comunidad' => 'all',
                'linea' => 'all',
                'tematica' => 'all',
                'title' => $title_param
            ),
            site_url('/index.php/resultados/radar-de-innovacion/')
        ) : null;


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
        ); // para cada proyecto crea un array y liuego lo convierte en json

    }
    $proyectos_json = json_encode($proyectos_data);

    ob_start();
    ?>
        <h2 class="bordered-title">LABORATORIO DE IDEAS</h2>

        <div id="buscador-proyectos">
            <!-- Contenedor de la tabla y el buscador -->
            <div class="panel-busqueda">
                <input type="text" id="busqueda" placeholder="Buscar proyectos..." onkeyup="buscarProyectos()"> 
                <!-- Buscador y select que cuando se tocan activan buscarProyectos -->
                <select id="filtro_comunidad" onchange="buscarProyectos()">
                    <option value="">Todas las comunidades</option>
                </select>
                <button id="btn-volver-todos" style="display: none; margin-top: 10px;">
                    Mostrar todos los proyectos
                </button>

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

            <!-- Popup -->
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
        const proyectosData = <?php echo $proyectos_json; ?>; //variable que contiene todos los proyectos json que antes guardamos

        document.addEventListener("DOMContentLoaded", function() { // esto espera que el DOM este listo antes de cargar nada
            cargarComunidades();

            const urlParams = new URLSearchParams(window.location.search);
            const buscarProyecto = urlParams.get("buscar"); //vemos si la url tiene algo de buscar

            if (buscarProyecto) {
                document.getElementById("busqueda").value = buscarProyecto; // si tiene lo de buscar rellena el campo de busqueda

                // Esperamos a que se carguen las comunidades y luego lanzamos buscarProyectos
                setTimeout(() => {
                    buscarProyectos();
                    const tableBody = document.querySelector("#tabla-proyectos tbody");
                    observer.observe(tableBody, { childList: true });
                }, 300); 

            } else { //si nobuscamos tb todos los proyectos y se genera el grafo general
                buscarProyectos();
                const tableBody = document.querySelector("#tabla-proyectos tbody");
                const observer = new MutationObserver(function(mutations) {
                    generarGrafoDesdeTablaCargada();
                });
                observer.observe(tableBody, { childList: true });
            }
        });
        
        //=======================================================================================
        function cargarComunidades() { //se ejecuta al principio, llama al ajax que obtiene la lista de comunidades
            fetch("<?php echo admin_url('admin-ajax.php?action=obtener_comunidades'); ?>")
                .then(response => response.json())
                .then(data => {
                    let select = document.getElementById("filtro_comunidad");
                    data.forEach(comunidad => {
                        let option = document.createElement("option");
                        option.value = comunidad;
                        option.textContent = comunidad;
                        select.appendChild(option);
                    }); //y crea el filtro
                });
        }

        //===========================================================================================

        function buscarProyectos() {
            let termino = document.getElementById("busqueda").value; //coge lo que hay en el buscador
            let comunidad = document.getElementById("filtro_comunidad").value; //coge lo que hay en el select

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
                asignarEventosPopup(); 
                generarGrafoDesdeTablaCargada();
            });
        }

        //===============================================================================
        function generarGrafoDesdeTablaCargada() {
            // contenedor de grafos
            const graficoContainer = document.getElementById("grafico-container-buscador");

            // siempre vsible
            graficoContainer.style.display = "block";

            // extrae los proyectos ed la tabla visible, para que cuando filtremos cambia el grafo
            const projectNames = Array.from(
                document.querySelectorAll("#tabla-proyectos tbody tr td:nth-child(1)")
            ).map(td => td.textContent.trim());

            generateGraph(projectNames, proyectosData, "network-container-buscador"); //funcion que genera el grafo
        }
        //=================================================================================



        function asignarEventosPopup() {
            let filas = document.querySelectorAll(".fila-proyecto");
            let popup = document.getElementById("popup-info");
            let isPopupFixed = false;
            let currentHighlightedRow = null;

            // Limpiar cualquier evento previo para evitar duplicados
            filas.forEach(fila => {
                const newFila = fila.cloneNode(true);
                fila.parentNode.replaceChild(newFila, fila);
            });

            // =======================================================Función para posicionar el popup inteligentemente
            function posicionarPopup(event) {
                const popupHeight = popup.offsetHeight;
                const popupWidth = popup.offsetWidth;
                const padding = 10;

                let top, left;

                const espacioAbajo = window.innerHeight - event.clientY;
                const espacioArriba = event.clientY;

                if (espacioAbajo >= popupHeight + padding) {
                    top = event.clientY + padding;
                } else if (espacioArriba >= popupHeight + padding) {
                    top = event.clientY - popupHeight - padding;
                } else {
                    top = Math.max(10, window.innerHeight - popupHeight - 10);
                }

                // posicionamiento horizontal
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

            //===================================================================================
            const tabla = document.getElementById("id-de-tu-tabla"); 

            if (tabla) {
                tabla.addEventListener("scroll", function() {
                    if (isPopupFixed) {
                        const closeButton = document.getElementById("popup-close");
                        if (closeButton) closeButton.remove();

                        popup.style.display = "none";
                        popup.style.border = "1px solid #ccc";
                        popup.style.backgroundColor = "#F4E8C8";
                        isPopupFixed = false;

                        resetRowHighlight();

                        if (window.projectNetwork) {
                            resetNodeHighlights();
                        }
                    }
                });
            }

            // Función para resetear fila resaltada 
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
                    resetTableHighlights() 
                    if (isPopupFixed) { // si esta abierto lo cierra el popup
                        const closeButton = document.getElementById("popup-close");
                        if (closeButton) {
                            closeButton.remove();
                        }

                        popup.style.border = "1px solid #ccc";
                        popup.style.backgroundColor = " #F4E8C8";
                        popup.style.display = "none";
                        isPopupFixed = false;
                        
                        // Reseteo de fila cuando se cierra
                        resetRowHighlight();
                        
                        if (window.projectNetwork) {
                            resetNodeHighlights();
                        }
                        
                        } else { //si no lo llena
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
                            
                            resetRowHighlight();
                            
                            if (window.projectNetwork) {
                                resetNodeHighlights();
                            }
                        });

                        popup.appendChild(closeButton);

                        popup.style.border = "2px solid #324093";
                        popup.style.backgroundColor = "#F4E8C8";
                        isPopupFixed = true;
                    }
                    
                    const projectName = fila.querySelector('td:first-child').textContent.trim();
                    resetRowHighlight();
                    if (window.projectNetwork) {
                        window.projectNetwork.selectNodes([projectName]);
                        window.projectNetwork.emit('selectNode', { nodes: [projectName] });
                    }                    
                    
                    fila.style.backgroundColor = 'rgb(255, 197, 197)';
                    fila.style.border = '2px solid #324093';
                    currentHighlightedRow = fila;
                    
                    if (window.projectNetwork) {
                        window.projectNetwork.focus(projectName, {
                            scale: 1.3,
                            animation: true
                        });
                        highlightNode(projectName);

                    }


                });
            });
            

            // cerramos popup cuando pulsamos en cualquier lado y esta abierto
            document.addEventListener("click", function(event) {
                if (!popup.contains(event.target) && isPopupFixed) {
                    const closeButton = document.getElementById("popup-close");
                    if (closeButton) closeButton.click();

                }
            });



        }  // fin asignarEventosPopup()

        //========================================================================
        // Modificar la función buscarProyectos para llamar a asignarEventosPopup después de cargar los datos
        function buscarProyectos() {
            let termino = document.getElementById("busqueda").value;
            let comunidad = document.getElementById("filtro_comunidad").value;

            let formData = new FormData();
            formData.append("action", "buscar_proyectos"); //va al ajax buscar proyectos con lo del buscador y el select
            formData.append("termino", termino);
            formData.append("comunidad", comunidad);

            fetch("<?php echo admin_url('admin-ajax.php'); ?>", { //hace la peticion
                method: "POST",
                body: formData
            })
            .then(response => response.text()) // le devuelve una respuesta html con las filas de la tabla
            .then(data => {
                document.querySelector("#tabla-proyectos tbody").innerHTML = data;
                asignarEventosPopup(); // asigna los eventos popup 
                generarGrafoDesdeTablaCargada(); //vuelve a generar el grafo con esas filas de tabla
            });
        }
        //========================================================================

        function generateGraph(selectedProjects, allProjectsData, containerId) {
            // Verificar que el contenedor existe
            const container = document.getElementById(containerId); //COMPROBAR QUE HAY CONTENEDOR
            if (!container) {
                console.error(`El contenedor con ID ${containerId} no existe en el DOM`);
                return null;
            }

            container.innerHTML = "";

            // COMPROBAR  que tenemos datos
            if (!allProjectsData || allProjectsData.length === 0) {
                console.error("No hay datos de proyectos disponibles");
                container.innerHTML = "<p>No se pudieron cargar los datos de proyectos</p>";
                return null;
            }

            const filteredProjects = allProjectsData.filter(project => 
                selectedProjects.includes(project.nombre)
            );

            // Si no hay proyectos filtrados, mostrar mensaje
            if (filteredProjects.length === 0) { // si no coincide ningun proyecto
                console.error("No hay proyectos seleccionados");
                container.innerHTML = "<p>Por favor seleccione al menos un proyecto</p>";
                return null;
            }

            let allKeywords = [];
            let projectNames = [];

            filteredProjects.forEach(project => { // para cada proyecto
                // Verificar que el proyecto tiene nombre y palabras clave
                if (!project.nombre || !project.palabras_clave) {
                    console.warn("Proyecto sin nombre o palabras clave:", project);
                    return; // Saltar este proyecto
                }

                projectNames.push(project.nombre);

                const cleanKeyword = kw =>
                    kw
                        .trim()
                        .toLowerCase()
                        .normalize('NFD')
                        .replace(/[\u0300-\u036f]/g, '') // eliminar acentos
                        .replace(/\s+/g, ' '); // reducir espacios múltiples

                const keywords = project.palabras_clave.split(',').map(cleanKeyword).filter(kw => kw.length > 0);

                allKeywords = allKeywords.concat(keywords);



                allKeywords = allKeywords.concat(keywords);
            });

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

            const nodes = new vis.DataSet();

            uniqueProjects.forEach(project => {
                nodes.add({
                    id: project,
                    label: project.replace(/(.{1,20})(\s+|$)/g, "$1\n").trim(),
                    color: '#324093', 
                    font: { color: '#000000', size: 12 },
                    size: 25
                });
            });

            uniqueKeywords.forEach(keyword => {
                nodes.add({
                    id: keyword,
                    label: keyword,
                    color: '#000000', 
                    font: { color: '#000000', size: 10 },
                    size: 15
                });
            });

            const edges = new vis.DataSet();

            filteredProjects.forEach(project => {
                const projectName = project.nombre;

                const keywords = project.palabras_clave.split(',').map(kw =>
                    kw.trim().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                );

                [...new Set(keywords)].forEach(keyword => {
                    edges.add({
                        from: projectName,
                        to: keyword,
                        color: { color: '#888888', opacity: 0.7 }
                    });
                });
            });

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
                    navigationButtons: false,
                    zoomView: true
                }
            };

            const network = new vis.Network(container, data, options);

            window.projectNetwork = network;

            let selectedNodeId = null;

            // Definir resetTableHighlights que faltaba
            function resetTableHighlights() { // Resetea el color de la tabla
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

                    if (uniqueProjects.includes(nodeId)) { // solo actuamos si es proyecto, no keyword
                        const relacionados = getRelatedProjects(nodeId, allProjectsData); //obtenemos proyectos que dcomparten keyword

                        document.getElementById("busqueda").value = ''; //limpiamos filttros  
                        document.getElementById("filtro_comunidad").selectedIndex = 0;
                        document.getElementById("btn-volver-todos").style.display = "inline-block";

                        document.getElementById("btn-volver-todos").addEventListener("click", function() { // si clickamos en este boton, lo quita y muestra todos los proyectos sin filtros
                            // Restablecer filtros si es necesario
                            document.getElementById("busqueda").value = '';
                            document.getElementById("filtro_comunidad").selectedIndex = 0;

                            // Ocultar el botón de volver
                            this.style.display = "none";

                            // Volver a cargar todos los proyectos
                            buscarProyectos();
                        });


                        const tableBody = document.querySelector("#tabla-proyectos tbody");
                        tableBody.innerHTML = ''; // limpiar

                        allProjectsData.forEach(p => {
                            if (relacionados.includes(p.nombre) && p.nombre && p.comunidad ) { //solo mostramos con comunidad y nombre (evitar filas vacías)
                                const fila = document.createElement("tr");
                                fila.classList.add("fila-proyecto");
                                if (!p.nombre.trim()) return;




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

                        asignarEventosPopup(); //evento de popup y grafo
                        generateGraph(relacionados, allProjectsData, "network-container-buscador");
                        setTimeout(() => {
                            if (window.projectNetwork && window.projectNetwork.body && window.projectNetwork.body.data) {
                                const nodes = window.projectNetwork.body.data.nodes;
                                const connected = window.projectNetwork.getConnectedNodes(nodeId);

                                // Aumentar tamaño del nodo principal
                                nodes.update({
                                    id: nodeId,
                                    size: 35,
                                    borderWidth: 3,
                                    color: 'rgb(252, 62, 62)'
                                });

                                connected.forEach(id => {
                                    nodes.update({
                                        id,
                                        size: 20
                                    });
                                });
                            }
                        }, 90); 


                        highlightTableRow(nodeId); 


                        selectedNodeId = nodeId;
                    }
                }
            });



            function highlightTableRow(projectName) {
                const cell = Array.from(
                    document.querySelectorAll("#tabla-proyectos tbody tr.fila-proyecto td:first-child")
                ).find(cell => cell.textContent.trim() === projectName);
                
                if (cell) {
                    const row = cell.parentNode;
                    
                    row.classList.add("highlighted-row");
                    row.style.backgroundColor = 'rgb(230, 243, 255)';
                    row.style.border = '2px solid #324093';
                    cell.style.fontWeight = 'bold';
 
                    const tableContainer = document.getElementById("tabla-proyectos").closest('.table-container') || 
                                        document.getElementById("tabla-proyectos").parentElement;
                    
                    if (tableContainer) {
                        const rowRect = row.getBoundingClientRect();
                        const containerRect = tableContainer.getBoundingClientRect();
                        
                        // Restamos un poco para que no quede exactamente al borde
                        const scrollTop = row.offsetTop - tableContainer.offsetTop - 4;
                        
                        // Desplazar suavemente 
                        tableContainer.scrollTo({
                            top: scrollTop,
                            behavior: 'smooth'
                        });
                    }
                }
            }

            function highlightNode(nodeId) { 
                resetHighlights();
                
                // Obtener todos los nodos conectados (palabras clave para este proyecto)
                const connectedNodes = network.getConnectedNodes(nodeId);
                
                // Resaltar el nodo
                nodes.update({
                    id: nodeId,
                    color: 'rgb(252, 62, 62)', 
                    // size: 30
                });
                
                // Resaltar aristas y nodos conectados
                for (let i = 0; i < connectedNodes.length; i++) {
                    const connectedNodeId = connectedNodes[i];
                    
                    // Resaltar nodo conectado
                    nodes.update({
                        id: connectedNodeId,
                        color: 'rgb(253, 198, 115)', 
                        // size: 20
                    });
                }
            }

            function resetHighlights() {
                nodes.forEach(node => {
                    if (node.id && typeof node.id === 'string') {
                        if (projectNames.includes(node.id)) {
                            nodes.update({
                                id: node.id,
                                color: '#324093', 
                                size: 25
                            });
                        } else {
                            nodes.update({
                                id: node.id,
                                color: '#000000', 
                                size: 15
                            });
                        }
                    }
                });
                
                resetTableHighlights();
                

            }

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

        function getRelatedProjects(projectName, allProjectsData) { //esta funcion cuando pulsas un nodo se llama y asi da una lisa de los proyectos relacionados
            const cleanKeyword = kw =>
                kw
                    .trim()
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '') // quitar acentos
                    .replace(/\s+/g, ' '); // reducir espacios múltiples

            const targetProject = allProjectsData.find(p => p.nombre === projectName);
            if (!targetProject || !targetProject.palabras_clave) return [];

            const targetKeywords = targetProject.palabras_clave
                .split(',')
                .map(cleanKeyword)
                .filter(kw => kw.length > 0);

            const relatedProjects = allProjectsData.filter(p => {
                if (p.nombre === projectName || !p.palabras_clave) return true;

                const keywords = p.palabras_clave
                    .split(',')
                    .map(cleanKeyword)
                    .filter(kw => kw.length > 0);

                return keywords.some(kw => targetKeywords.includes(kw));
            });

            return relatedProjects.map(p => p.nombre);
        }

        //==============================================================================
        // FUNCION PARA BUSCAR LA FILA Y SCROLEAR HASTA ELLA - FUNCION QUE VA LENTA CREO
        let isSearchingRow = false;

        //=============================================================
        // Función para resetear los nodos destacados en el grafo
        function resetNodeHighlights() {
            if (window.projectNetwork) {
                const nodes = window.projectNetwork.body.data.nodes;
                const edges = window.projectNetwork.body.data.edges;
                
                nodes.forEach(node => {
                    if (node.id && typeof node.id === 'string') {
                        const isProjectNode = projectNames.includes(node.id);
                        nodes.update({
                            id: node.id,
                            color: isProjectNode ? ' #324093' : ' #000000', 
                            size: isProjectNode ? 25 : 15
                        });
                    }
                });
                
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
        function resetTableHighlights() {
            const rows = document.querySelectorAll('#tabla-proyectos tbody tr');
            rows.forEach(row => {
                row.style.backgroundColor = '';
                row.style.border = '';
            });
            currentHighlightedRow = null;

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
            font-family: "Monserrat", Sans-serif;
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
            margin-left: -20px; 

        }

        .panel-busqueda {
            flex: 3;
            width: 60%;
            min-width: 500px;
            max-width: 1000px;
            box-sizing: border-box;
            margin-top: 45px; 

        }
        #busqueda,
        #filtro_comunidad, #btn-volver-todos {
            font-size: 1.2em;
            padding: 8px 12px;
            background-color: #F4E8C8;  /* color personalizado */
            border: 1px solid #ccc;
            border-radius: 6px;
            color: #324093;
            outline: none;
            transition: box-shadow 0.2s ease;
            margin-bottom: 10px; 

        }

        #busqueda::placeholder {
            color: #324093;
        }

        #busqueda:focus,
        #filtro_comunidad:focus {
            box-shadow: 0 0 5px rgba(50, 64, 147, 0.5);  
            border-color: #324093;  
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
        font-size: 32px; /* Tamaño fijo y equilibrado */
        line-height: 1.2;
        color: #324093;
        padding: 10px 20px;
        margin: 20px auto;
        text-align: center;

        border: 4px solid #324093;
        background-color: transparent;
        box-shadow: 4px 4px 0px rgba(50, 64, 147, 0.9);
        border-radius: 8px;

        width: 100%;
        max-width: 600px;
        box-sizing: border-box;
    }

        #popup-info {
            position: fixed; /* Cambiar de absolute a fixed */
            background: #F4E8C8;
            border: 1px solid #ccc;
            padding: 10px;
            box-shadow: 2px 2px 10px rgba(0,0,0,0.2);
            display: none;
            max-width: 250px;
            z-index: 1000;
            font-size: 14px;
            max-width: 300px; 
            word-wrap: break-word; 
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


add_shortcode('buscador_proyectos', 'buscador_proyectos_shortcode');
//========================================================================================================



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

    $termino = isset($_POST['termino']) ? sanitize_text_field($_POST['termino']) : ''; // lo que le pasamos
    $comunidad = isset($_POST['comunidad']) ? sanitize_text_field($_POST['comunidad']) : '';

    $query = "SELECT * FROM $tabla_proyectos WHERE 1=1"; //petiicion a la base de datos donde coincida la busqueda y la comunidad

    if (!empty($termino)) {
        $query .= " AND (nombre_proyecto LIKE %s OR persona_contacto LIKE %s OR palabras_clave LIKE %s)";
    }

    if (!empty($comunidad)) {
        $query .= " AND comunidad_autonoma = %s";
    } // si no estan vacios añade unas consicionesa los filtros, ya que no tiene que ser coincidencia exacta y busca tanto en nombre del proyecto, persona de contacto
    //o palabra clave

    $query .= " ORDER BY id";

    $params = [];
    if (!empty($termino)) {
        $busqueda = '%' . $wpdb->esc_like($termino) . '%';
        array_push($params, $busqueda, $busqueda, $busqueda);
    }

    if (!empty($comunidad)) {
        array_push($params, $comunidad);
    }

    $resultados = empty($params) ? $wpdb->get_results($query) : $wpdb->get_results($wpdb->prepare($query, ...$params)); // ejecucion de la consulta, si no hay filtros ejecuta sin preparar
    // 

    if ($resultados) {
        foreach ($resultados as $index => $proyecto) {
            if (empty(trim($proyecto->nombre_proyecto))) continue;
            // Extraer el número de línea de actuación
            $linea_numero = '-';
            if (preg_match('/Línea de Actuación\s*(\d+):/i', $proyecto->linea_actuacion, $matches)) {
                $linea_numero = $matches[1];
            }

        $titles_map = [ //mapa que relaciona el titulo del proyecto con la ficha por su title
        'Ciencia de datos para la emergencia de inteligencia en el medio marino y litoral a través de la monitorización ambiental' => 'CIENCIADEDATOS',
        'OMEMAR' => 'OMEMAR',
        'Clasificador de Eventos Acústicos Submarinos' => 'CEAS',
        'OMM-Azul' => 'OMMazul',
        'Observatorio de la Gobernanza Marina (OGMAR)'=> 'OGMAR',
        'UEM-IEO-CSIC' =>'CSIC',
        'Monitorización Marina Mediante Equipos de AUVs Colaborativos' =>'AUVCOLLAB',
        'Mamíferos marinos como inidcadores de riesgos por contaminantes ambientales emergentes en las costas de la Región de Murcia (MARFARISK)'=>'MARFARISK',


        
        ];

            $title_param = isset($titles_map[$proyecto->nombre_proyecto]) ? $titles_map[$proyecto->nombre_proyecto] : null;
            $ficha_url = $title_param ? add_query_arg(
                array(
                    'comunidad' => 'all',
                    'linea' => 'all',
                    'tematica' => 'all',
                    'title' => $title_param
                ),
                site_url('/index.php/resultados/radar-de-innovacion/')
            ) : null;


            
// cada resultado sera una fila 
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
//En esta función es donde obtiene los filtros de la tabla, es decir aqui mira la tabla y coge las comunidades autonomas que haya para hacer el select

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

