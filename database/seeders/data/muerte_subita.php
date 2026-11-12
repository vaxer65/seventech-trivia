<?php

/**
 * Banco de Preguntas — Muerte Súbita / Desempate (Dificilísimas)
 * Total: 40 preguntas reales de altísima precisión técnica e histórica (4 por cada uno de los 10 módulos).
 * Todas técnicas (es_amena = false).
 * Balance de respuestas correctas: exactamente 10 'a', 10 'b', 10 'c', 10 'd'.
 */
return [
    // =========================================================================
    // MÓDULO 1: Fundamentos de Programación & Lógica (4 preguntas)
    // =========================================================================
    [
        'modulo' => 'Fundamentos de Programación & Lógica',
        'enunciado' => 'En teoría de la computación, ¿cuál de los siguientes problemas clásicos fue demostrado matemáticamente por Cook y Levin en 1971 como el primer problema NP-completo de la historia?',
        'opcion_a' => 'Boolean Satisfiability Problem (SAT)',
        'opcion_b' => 'Multiplicación de matrices cuadradas',
        'opcion_c' => 'Búsqueda lineal en arreglos desordenados',
        'opcion_d' => 'Generación de números pseudoaleatorios en C',
        'respuesta_correcta' => 'a',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Fundamentos de Programación & Lógica',
        'enunciado' => 'En el cálculo lambda de Alonzo Church (1936), ¿cómo se llama el combinador de punto fijo más célebre que permite implementar recursión en lenguajes que no tienen nombres para funciones?',
        'opcion_a' => 'Combinador Omega (Ω)',
        'opcion_b' => 'Combinador Y (Y Combinator)',
        'opcion_c' => 'Combinador Delta (Δ)',
        'opcion_d' => 'Combinador Iota (ι)',
        'respuesta_correcta' => 'b',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Fundamentos de Programación & Lógica',
        'enunciado' => 'En representación de punto flotante estándar IEEE 754 de precisión simple (32 bits), ¿cuántos bits se destinan respectivamente al signo, al exponente con sesgo y a la mantisa o fracción?',
        'opcion_a' => '2 bits de signo, 10 de exponente y 20 de mantisa',
        'opcion_b' => '1 bit de signo, 15 de exponente y 16 de mantisa',
        'opcion_c' => '1 bit de signo, 8 bits de exponente y 23 bits de mantisa',
        'opcion_d' => '4 bits de signo, 12 de exponente y 16 de mantisa',
        'respuesta_correcta' => 'c',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Fundamentos de Programación & Lógica',
        'enunciado' => 'En la jerarquía de Chomsky de lenguajes formales, ¿qué autómata teórico tiene el poder computacional exacto necesario para reconocer los Lenguajes Libres de Contexto (Tipo 2)?',
        'opcion_a' => 'Autómata Finito Determinista (DFA)',
        'opcion_b' => 'Autómata Linealmente Acotado (LBA)',
        'opcion_c' => 'Máquina de Turing no determinista universal',
        'opcion_d' => 'Autómata con Pila (Pushdown Automaton)',
        'respuesta_correcta' => 'd',
        'es_amena' => false,
    ],

    // =========================================================================
    // MÓDULO 2: Desarrollo Web Frontend (HTML, CSS, JS) (4 preguntas)
    // =========================================================================
    [
        'modulo' => 'Desarrollo Web Frontend (HTML, CSS, JS)',
        'enunciado' => 'En la API de rendimiento web del W3C, ¿qué métrica de Core Web Vitals (CWV) mide la latencia de respuesta ante la interacción más lenta del usuario durante toda su sesión en la página?',
        'opcion_a' => 'INP (Interaction to Next Paint)',
        'opcion_b' => 'FCP (First Contentful Paint)',
        'opcion_c' => 'LCP (Largest Contentful Paint)',
        'opcion_d' => 'CLS (Cumulative Layout Shift)',
        'respuesta_correcta' => 'a',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Desarrollo Web Frontend (HTML, CSS, JS)',
        'enunciado' => 'En el motor V8 de Google Chrome, ¿cómo se denominan respectivamente el compilador base ultra-rápido y el compilador optimizador JIT de alto rendimiento?',
        'opcion_a' => 'Blink y WebKit',
        'opcion_b' => 'Ignition (intérprete/bytecode) y TurboFan (compilador optimizador JIT)',
        'opcion_c' => 'SpiderMonkey e IonMonkey',
        'opcion_d' => 'Gecko y KHTML',
        'respuesta_correcta' => 'b',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Desarrollo Web Frontend (HTML, CSS, JS)',
        'enunciado' => 'En JavaScript avanzado (ES6+), ¿qué estructura de datos permite almacenar referencias débiles a objetos sin impedir que el recolector de basura (Garbage Collector) los libere si no quedan otras referencias?',
        'opcion_a' => 'Map',
        'opcion_b' => 'ArrayBuffer',
        'opcion_c' => 'WeakMap (y WeakSet)',
        'opcion_d' => 'DataView',
        'respuesta_correcta' => 'c',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Desarrollo Web Frontend (HTML, CSS, JS)',
        'enunciado' => 'En especificación ECMAScript, ¿cuál de los siguientes símbolos nativos de JavaScript permite personalizar cómo un objeto se convierte a una clave primitiva o iterador en `for...of`?',
        'opcion_a' => 'Symbol.toStringTag',
        'opcion_b' => 'Symbol.hasInstance',
        'opcion_c' => 'Symbol.isConcatSpreadable',
        'opcion_d' => 'Symbol.iterator',
        'respuesta_correcta' => 'd',
        'es_amena' => false,
    ],

    // =========================================================================
    // MÓDULO 3: Backend, APIs, PHP & Laravel (4 preguntas)
    // =========================================================================
    [
        'modulo' => 'Backend, APIs, PHP & Laravel',
        'enunciado' => 'En el ciclo de arranque de Laravel, ¿en qué clase fundamental del framework se registran los proveedores de servicios del núcleo y se despacha la petición a través del pipeline de Middleware global?',
        'opcion_a' => 'Illuminate\Foundation\Http\Kernel',
        'opcion_b' => 'Illuminate\Support\Facades\Route',
        'opcion_c' => 'Illuminate\Database\Eloquent\Builder',
        'opcion_d' => 'Illuminate\View\Factory',
        'respuesta_correcta' => 'a',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Backend, APIs, PHP & Laravel',
        'enunciado' => 'En la máquina virtual de PHP (Zend Engine), ¿cómo se llama la estructura C interna de 16 bytes que representa cualquier variable o valor de PHP en memoria?',
        'opcion_a' => 'zend_closure',
        'opcion_b' => '_zval_struct (zval)',
        'opcion_c' => 'php_stream',
        'opcion_d' => 'zend_hash_key',
        'respuesta_correcta' => 'b',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Backend, APIs, PHP & Laravel',
        'enunciado' => 'En arquitectura de bases de datos de Laravel, ¿qué método de Eloquent permite escuchar cada evento disparado por un modelo (`creating`, `updating`, `deleted`) en clases dedicadas sin ensuciar los controladores?',
        'opcion_a' => 'Model Factories',
        'opcion_b' => 'Database Seeders',
        'opcion_c' => 'Model Observers',
        'opcion_d' => 'Global Scopes de Macro',
        'respuesta_correcta' => 'c',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Backend, APIs, PHP & Laravel',
        'enunciado' => 'En el algoritmo de cifrado de contraseñas Bcrypt (`$2y$12$...`), ¿qué significa exactamente el parámetro de coste numérico (en este caso 12)?',
        'opcion_a' => 'Que la contraseña se cifra con una clave de 12 bytes',
        'opcion_b' => 'Que el hash expira a los 12 meses de creado',
        'opcion_c' => 'Que el hash se trunca en la posición 12',
        'opcion_d' => 'Que la función interna realiza $2^{12} = 4096$ rondas de expansión de clave sobre el cifrador Blowfish',
        'respuesta_correcta' => 'd',
        'es_amena' => false,
    ],

    // =========================================================================
    // MÓDULO 4: Bases de Datos Relacionales & SQL (4 preguntas)
    // =========================================================================
    [
        'modulo' => 'Bases de Datos Relacionales & SQL',
        'enunciado' => 'En el motor InnoDB de MySQL, ¿qué tipo de índice almacena físicamente las filas completas de datos de la tabla ordenadas directamente en las hojas del árbol B+ por su clave primaria?',
        'opcion_a' => 'Índice Clúster (Clustered Index)',
        'opcion_b' => 'Índice Secundario Disperso',
        'opcion_c' => 'Índice Full-Text Invertido',
        'opcion_d' => 'Índice Hash en Memoria RAM',
        'respuesta_correcta' => 'a',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Bases de Datos Relacionales & SQL',
        'enunciado' => 'En el log de transacciones de MySQL InnoDB, ¿cuál es el componente que escribe secuencialmente en disco cada cambio antes de sincronizar las páginas de datos en los archivos `.ibd` para garantizar Durabilidad ante cortes de energía (WAL)?',
        'opcion_a' => 'Binary Log binlog',
        'opcion_b' => 'Redo Log (junto con el archivo ib_logfile)',
        'opcion_c' => 'Slow Query Log',
        'opcion_d' => 'General Query Log',
        'respuesta_correcta' => 'b',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Bases de Datos Relacionales & SQL',
        'enunciado' => 'Bajo el nivel de aislamiento `REPEATABLE READ` en InnoDB, ¿qué tipo de cerrojo especial previene lecturas fantasma (Phantom Reads) bloqueando el intervalo entre dos registros indexados existentes?',
        'opcion_a' => 'Record Lock simple',
        'opcion_b' => 'Table Lock global',
        'opcion_c' => 'Gap Lock (y Next-Key Lock)',
        'opcion_d' => 'Shared Lock en memoria swap',
        'respuesta_correcta' => 'c',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Bases de Datos Relacionales & SQL',
        'enunciado' => 'En el álgebra relacional formal que fundamenta a SQL, ¿qué operador unario reduce las columnas de una relación seleccionando un subconjunto de atributos verticales (análogo al SELECT de columnas específicas)?',
        'opcion_a' => 'Selección (sigma: $\sigma$)',
        'opcion_b' => 'Unión ($\cup$)',
        'opcion_c' => 'Diferencia de conjuntos ($-$)',
        'opcion_d' => 'Proyección (pi: $\pi$)',
        'respuesta_correcta' => 'd',
        'es_amena' => false,
    ],

    // =========================================================================
    // MÓDULO 5: Estructuras de Datos, Algoritmos & POO (4 preguntas)
    // =========================================================================
    [
        'modulo' => 'Estructuras de Datos, Algoritmos & POO',
        'enunciado' => 'En árboles binarios de búsqueda autobalanceados AVL, ¿cuál es la diferencia máxima permitida entre las alturas de los subárboles izquierdo y derecho de cualquier nodo (Factor de Equilibrio)?',
        'opcion_a' => 'Como máximo 1 (valores permitidos: -1, 0, +1)',
        'opcion_b' => 'Exactamente 2 en todos los niveles',
        'opcion_c' => 'Cero en todo momento (estrictamente simétrico)',
        'opcion_d' => 'Hasta $\log_2(N)$ de diferencia',
        'respuesta_correcta' => 'a',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Estructuras de Datos, Algoritmos & POO',
        'enunciado' => '¿Cuál de las siguientes estructuras probabilísticas de datos permite comprobar si un elemento pertenece a un conjunto en $O(1)$ sin falsos negativos, aunque tolerando una tasa controlable de falsos positivos?',
        'opcion_a' => 'Árbol Rojo-Negro',
        'opcion_b' => 'Filtro de Bloom (Bloom Filter)',
        'opcion_c' => 'Trie Compacto',
        'opcion_d' => 'Montículo de Fibonacci',
        'respuesta_correcta' => 'b',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Estructuras de Datos, Algoritmos & POO',
        'enunciado' => 'En teoría de la complejidad computacional, ¿cuál es la cota inferior teórica demostrada para cualquier algoritmo determinista de ordenamiento basado exclusivamente en comparaciones de elementos en el peor caso?',
        'opcion_a' => '$\Omega(N)$',
        'opcion_b' => '$\Omega(N^2)$',
        'opcion_c' => '$\Omega(N \log N)$',
        'opcion_d' => '$\Omega(\log N)$',
        'respuesta_correcta' => 'c',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Estructuras de Datos, Algoritmos & POO',
        'enunciado' => 'En un Montículo Binario Máximo (Max Heap) almacenado contiguamente en un arreglo indexado desde 0, si un nodo se encuentra en la posición $i$, ¿en qué índices se encuentran sus dos hijos izquierdo y derecho?',
        'opcion_a' => '$i+1$ e $i+2$',
        'opcion_b' => '$i/2$ e $i/2 + 1$',
        'opcion_c' => '$2i$ y $2i + 1$',
        'opcion_d' => '$2i + 1$ y $2i + 2$',
        'respuesta_correcta' => 'd',
        'es_amena' => false,
    ],

    // =========================================================================
    // MÓDULO 6: Sistemas Operativos, Linux & DevOps (4 preguntas)
    // =========================================================================
    [
        'modulo' => 'Sistemas Operativos, Linux & DevOps',
        'enunciado' => 'En el kernel de Linux moderno, ¿qué subsistema de instrumentación y observabilidad en tiempo de ejecución permite ejecutar programas seguros en modo kernel sin recompilar el núcleo ni cargar módulos no verificados?',
        'opcion_a' => 'eBPF (Extended Berkeley Packet Filter)',
        'opcion_b' => 'SELinux Enforcing',
        'opcion_c' => 'AppArmor Profile',
        'opcion_d' => 'Ulimit Daemon',
        'respuesta_correcta' => 'a',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Sistemas Operativos, Linux & DevOps',
        'enunciado' => 'En Linux, ¿cuál es la diferencia crucial entre las llamadas al sistema `fork()` y `vfork()` (o `clone()` con banderas de memoria compartida)?',
        'opcion_a' => 'vfork() solo se ejecuta en procesadores de un solo núcleo',
        'opcion_b' => 'fork() utiliza Copy-on-Write (CoW) duplicando tablas de páginas, mientras que vfork() suspende al padre y comparte el mismo espacio de memoria hasta que el hijo ejecuta execve() o _exit()',
        'opcion_c' => 'fork() no puede crear más de dos procesos hijos',
        'opcion_d' => 'vfork() cambia la prioridad nice a -20 inmediatamente',
        'respuesta_correcta' => 'b',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Sistemas Operativos, Linux & DevOps',
        'enunciado' => 'En memoria virtual de Linux, ¿qué demonio del kernel se activa automáticamente como último recurso cuando la RAM física y la Swap se saturan al 100%, terminando procesos según su puntuación oom_score?',
        'opcion_a' => 'systemd-journald',
        'opcion_b' => 'kcompactd0',
        'opcion_c' => 'OOM Killer (Out of Memory Killer)',
        'opcion_d' => 'cron.daily',
        'respuesta_correcta' => 'c',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Sistemas Operativos, Linux & DevOps',
        'enunciado' => 'En balanceo de carga web de ultra-alta concurrencia (como Nginx o HAProxy en Linux), ¿qué llamada del sistema reemplazó a `select()` y `poll()` eliminando la complejidad $O(N)$ al monitorizar decenas de miles de descriptores de socket?',
        'opcion_a' => 'readv()',
        'opcion_b' => 'ptrace()',
        'opcion_c' => 'ioctl()',
        'opcion_d' => 'epoll (epoll_create, epoll_ctl, epoll_wait)',
        'respuesta_correcta' => 'd',
        'es_amena' => false,
    ],

    // =========================================================================
    // MÓDULO 7: Redes de Datos, Protocolos & Cloud (4 preguntas)
    // =========================================================================
    [
        'modulo' => 'Redes de Datos, Protocolos & Cloud',
        'enunciado' => 'En control de congestión del protocolo TCP, ¿cuáles son las dos fases clásicas de ajuste de ventana implementadas en algoritmos como TCP Tahoe o Reno tras detectar una pérdida?',
        'opcion_a' => 'Slow Start (Arranque Lento) y Congestion Avoidance (Prevención de Congestión)',
        'opcion_b' => 'Ping Flood y Syn Flood',
        'opcion_c' => 'Round Robin y Least Connections',
        'opcion_d' => 'Half-Duplex y Full-Duplex',
        'respuesta_correcta' => 'a',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Redes de Datos, Protocolos & Cloud',
        'enunciado' => '¿Cuántos bits tiene exactamente el espacio de direcciones de IPv6, permitiendo aproximadamente $3.4 \times 10^{38}$ direcciones únicas en todo el planeta?',
        'opcion_a' => '64 bits',
        'opcion_b' => '128 bits (16 octetos)',
        'opcion_c' => '256 bits',
        'opcion_d' => '512 bits',
        'respuesta_correcta' => 'b',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Redes de Datos, Protocolos & Cloud',
        'enunciado' => 'En el protocolo BGP (Border Gateway Protocol), ¿qué métrica o tipo de algoritmo de enrutamiento utiliza entre Sistemas Autónomos en lugar de simplemente contar saltos o ancho de banda?',
        'opcion_a' => 'Algoritmo de Bellman-Ford clásico',
        'opcion_b' => 'Inundación por difusión OSPF',
        'opcion_c' => 'Protocolo Vector-Ruta (Path Vector) basado en políticas y atributos de ruta (AS-Path)',
        'opcion_d' => 'Árbol de expansión STP 802.1D',
        'respuesta_correcta' => 'c',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Redes de Datos, Protocolos & Cloud',
        'enunciado' => 'En el protocolo DNS seguro (DNSSEC), ¿qué tipo de registro criptográfico contiene las firmas digitales de los conjuntos de registros (RRset) para validar que la respuesta no fue envenenada en tránsito?',
        'opcion_a' => 'Registro SOA',
        'opcion_b' => 'Registro MX',
        'opcion_c' => 'Registro TXT',
        'opcion_d' => 'Registro RRSIG',
        'respuesta_correcta' => 'd',
        'es_amena' => false,
    ],

    // =========================================================================
    // MÓDULO 8: Ciberseguridad, Hacking Ético & Criptografía (4 preguntas)
    // =========================================================================
    [
        'modulo' => 'Ciberseguridad, Hacking Ético & Criptografía',
        'enunciado' => 'En criptografía de Curvas Elípticas (ECC), ¿en qué problema matemático fundamental intratable computacionalmente se basa la seguridad de algoritmos como Ed25519 o secp256k1?',
        'opcion_a' => 'El Problema del Logaritmo Discreto en Curvas Elípticas (ECDLP)',
        'opcion_b' => 'La factorización de números pares en factores primos',
        'opcion_c' => 'La resolución de ecuaciones polinomiales de segundo grado',
        'opcion_d' => 'El ordenamiento de cadenas de texto en memoria caché',
        'respuesta_correcta' => 'a',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Ciberseguridad, Hacking Ético & Criptografía',
        'enunciado' => 'En criptografía simétrica de bloques (AES), ¿qué modo de operación proporciona cifrado autenticado con datos asociados (AEAD), garantizando tanto confidencialidad como integridad de los datos en un solo paso?',
        'opcion_a' => 'ECB (Electronic Codebook)',
        'opcion_b' => 'GCM (Galois/Counter Mode)',
        'opcion_c' => 'CBC (Cipher Block Chaining sin HMAC)',
        'opcion_d' => 'CFB (Cipher Feedback)',
        'respuesta_correcta' => 'b',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Ciberseguridad, Hacking Ético & Criptografía',
        'enunciado' => 'En arquitectura de hardware y seguridad de procesadores, ¿a qué familia de ataques por canal lateral de ejecución especulativa pertenecen las vulnerabilidades "Spectre" y "Meltdown"?',
        'opcion_a' => 'Inyecciones de dependencias por red Wi-Fi',
        'opcion_b' => 'Ataques de denegación de servicio por ping ICMP',
        'opcion_c' => 'Ataques que explotan la predicción de saltos de la CPU y la memoria caché para leer regiones de memoria privilegiada del kernel o de otros procesos',
        'opcion_d' => 'Ataques de fuerza bruta contra el teclado físico',
        'respuesta_correcta' => 'c',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Ciberseguridad, Hacking Ético & Criptografía',
        'enunciado' => 'En el estándar de contraseñas de derivación de claves, ¿por qué "Argon2" (ganador del Password Hashing Competition en 2015) es superior a SHA-256 o PBKDF2 frente a ataques de crackers con tarjetas gráficas o ASICs?',
        'opcion_a' => 'Porque no utiliza ningún tipo de sal en el proceso',
        'opcion_b' => 'Porque cifra las contraseñas en exactamente 1 nanosegundo',
        'opcion_c' => 'Porque solo puede ejecutarse en sistemas con procesadores cuánticos',
        'opcion_d' => 'Porque es un algoritmo intensivo en memoria (memory-hard), lo que encarece y vuelve inviable el paralelismo masivo en hardware dedicado (GPU/ASIC)',
        'respuesta_correcta' => 'd',
        'es_amena' => false,
    ],

    // =========================================================================
    // MÓDULO 9: Inteligencia Artificial, Datos & Tendencias Tech (4 preguntas)
    // =========================================================================
    [
        'modulo' => 'Inteligencia Artificial, Datos & Tendencias Tech',
        'enunciado' => 'En la fórmula matemática de atención escalada de un Transformer, `Attention(Q, K, V) = softmax((Q * K^T) / sqrt(d_k)) * V`, ¿para qué se divide exactamente entre `sqrt(d_k)`?',
        'opcion_a' => 'Para evitar que con dimensiones altas los productos punto crezcan en magnitud y empujen la función Softmax hacia regiones de gradientes extremadamente pequeños',
        'opcion_b' => 'Para convertir las matrices a números enteros de 8 bits',
        'opcion_c' => 'Para calcular el logaritmo natural de la tasa de aprendizaje',
        'opcion_d' => 'Para invertir el orden de los tokens en la salida',
        'respuesta_correcta' => 'a',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Inteligencia Artificial, Datos & Tendencias Tech',
        'enunciado' => 'En optimizadores de redes neuronales profundas, ¿qué distingue al optimizador "AdamW" del optimizador "Adam" clásico con respecto al decaimiento de pesos (Weight Decay)?',
        'opcion_a' => 'AdamW desactiva la retropropagación en las primeras diez épocas',
        'opcion_b' => 'AdamW desacopla (decouples) el decaimiento de pesos de las actualizaciones basadas en los momentos del gradiente, aplicando regularización L2 real',
        'opcion_c' => 'AdamW solo puede entrenar redes de una sola neurona',
        'opcion_d' => 'AdamW duplica la tasa de aprendizaje cada vez que la pérdida aumenta',
        'respuesta_correcta' => 'b',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Inteligencia Artificial, Datos & Tendencias Tech',
        'enunciado' => 'En modelos generativos de difusión (Diffusion Models) utilizados para síntesis de imágenes de vanguardia, ¿cuál es el proceso físico-matemático en el que se basa su entrenamiento?',
        'opcion_a' => 'En comprimir archivos JPEG con pérdida hasta encontrar una plantilla',
        'opcion_b' => 'En competir dos redes llamadas Generador y Discriminador en un juego de suma cero',
        'opcion_c' => 'En añadir gradualmente ruido gaussiano a los datos (proceso directo) y entrenar una red neuronal para revertir el proceso eliminando el ruido paso a paso (proceso inverso)',
        'opcion_d' => 'En buscar imágenes similares en Google Images mediante scraping web',
        'respuesta_correcta' => 'c',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Inteligencia Artificial, Datos & Tendencias Tech',
        'enunciado' => 'En el contexto de modelos fundacionales de gran escala, ¿qué es la técnica "Mixture of Experts" (MoE) implementada en arquitecturas como Mixtral o Gemini?',
        'opcion_a' => 'Un foro de discusión donde ingenieros humanos califican las respuestas',
        'opcion_b' => 'Un banco de pruebas que corre benchmarks en diez servidores diferentes',
        'opcion_c' => 'Un ensamblado de modelos que votan por mayoría simple al final',
        'opcion_d' => 'Una arquitectura dispersa donde un mecanismo de enrutamiento (router) activa únicamente un subconjunto específico de subredes expertas para cada token procesado',
        'respuesta_correcta' => 'd',
        'es_amena' => false,
    ],

    // =========================================================================
    // MÓDULO 10: Cultura Geek, Historia de la Computación & Gaming (4 preguntas)
    // =========================================================================
    [
        'modulo' => 'Cultura Geek, Historia de la Computación & Gaming',
        'enunciado' => '¿En qué año se produjo la primera transmisión de un mensaje entre dos computadoras a través de ARPANET (cuando intentaron escribir "LOGIN" y el sistema colapsó tras enviar "LO")?',
        'opcion_a' => '1969',
        'opcion_b' => '1981',
        'opcion_c' => '1955',
        'opcion_d' => '1976',
        'respuesta_correcta' => 'a',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Cultura Geek, Historia de la Computación & Gaming',
        'enunciado' => '¿Qué científico de la computación holandés escribió en 1968 la influyente carta abierta "Go To Statement Considered Harmful", impulsando la adopción de la programación estructurada?',
        'opcion_a' => 'Donald Knuth',
        'opcion_b' => 'Edsger W. Dijkstra',
        'opcion_c' => 'Tony Hoare',
        'opcion_d' => 'Ken Thompson',
        'respuesta_correcta' => 'b',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Cultura Geek, Historia de la Computación & Gaming',
        'enunciado' => '¿Cómo se llamaba la pionera máquina electromecánica diseñada por Alan Turing y Gordon Welchman en Bletchley Park para descifrar las comunicaciones de la máquina Enigma en la Segunda Guerra Mundial?',
        'opcion_a' => 'Colossus',
        'opcion_b' => 'ENIAC',
        'opcion_c' => 'La Bomba (The Bombe)',
        'opcion_d' => 'UNIVAC I',
        'respuesta_correcta' => 'c',
        'es_amena' => false,
    ],
    [
        'modulo' => 'Cultura Geek, Historia de la Computación & Gaming',
        'enunciado' => '¿En qué fecha exacta del tiempo Unix (Epoch Time de 32 bits con signo) ocurrirá el temido "Problema del año 2038" (Y2038), cuando el contador de segundos supere los $2^{31}-1$ y se desborde a 1901?',
        'opcion_a' => '1 de enero de 2038 a las 00:00:00 UTC',
        'opcion_b' => '31 de diciembre de 2038 a las 23:59:59 UTC',
        'opcion_c' => '29 de febrero de 2038 a las 12:00:00 UTC',
        'opcion_d' => '19 de enero de 2038 a las 03:14:07 UTC',
        'respuesta_correcta' => 'd',
        'es_amena' => false,
    ],
];

