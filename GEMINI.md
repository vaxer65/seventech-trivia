# Reglas y Contexto del Proyecto — seventech-trivia

- **Nombre del proyecto:** `seventech-trivia`
- **Ruta del proyecto:** `/home/vaxer/seventech-trivia`

## Uso de Memoria MCP (Knowledge Graph)

- Usa el servidor MCP `memory` (Knowledge Graph) al inicio de las sesiones y durante el trabajo para consultar y almacenar entidades, relaciones y decisiones clave del proyecto.
- Prioriza consultar el Knowledge Graph antes de explorar o re-leer archivos innecesariamente, con el objetivo de ahorrar tokens y mantener continuidad entre sesiones.
- Actualiza el Knowledge Graph cuando descubras o modifiques arquitectura, modelos, controladores, convenciones o decisiones importantes.

## Reglas Técnicas Clave

- **Ordenamiento de preguntas de trivia:** Las preguntas se ordenan de forma pseudoaleatoria e idéntica usando `semilla_partida` mediante `CRC32(CONCAT(id, ?))` (implementado en `app/Http/Controllers/ConcursanteController.php`).

