<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monitor del servidor</title>
    <style>
        body {
            margin: 0;
            padding: 2rem;
            background: #f3f4f6;
            color: #1f2937;
            font-family: Arial, sans-serif;
        }

        main {
            max-width: 1000px;
            margin: 0 auto;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1rem;
        }

        .card {
            padding: 1.25rem;
            background: #fff;
            border-radius: 0.75rem;
            box-shadow: 0 4px 12px rgb(0 0 0 / 8%);
        }

        .card h2 {
            margin: 0 0 0.75rem;
            color: #4b5563;
            font-size: 1rem;
        }

        .value {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 700;
            overflow-wrap: anywhere;
        }
    </style>
</head>
<body>
<main>
    <h1>Monitor del servidor</h1>
    <p>Última lectura: <span id="at">{{ $data['at'] }}</span></p>

    <section class="grid">
        <article class="card">
            <h2>Nombre del equipo</h2>
            <p class="value">{{ $data['hostname'] ?? 'No disponible' }}</p>
        </article>
        <article class="card">
            <h2>Sistema operativo</h2>
            <p class="value">{{ $data['os'] ?? 'No disponible' }}</p>
        </article>
        <article class="card">
            <h2>Versión de PHP</h2>
            <p class="value">{{ $data['php'] ?? 'No disponible' }}</p>
        </article>
        <article class="card">
            <h2>Disco usado (%)</h2>
            <p class="value" id="disk">{{ $data['disk']['used_pct'] !== null ? $data['disk']['used_pct'].'%' : 'No disponible' }}</p>
        </article>
        <article class="card">
            <h2>Memoria usada (%)</h2>
            <p class="value" id="memory">{{ $data['memory']['used_pct'] ?? 'No disponible' }}{{ $data['memory'] !== null ? '%' : '' }}</p>
        </article>
        <article class="card">
            <h2>Uso de CPU (%)</h2>
            <p class="value" id="cpu">{{ $data['cpu_pct'] !== null ? $data['cpu_pct'].'%' : 'No disponible' }}</p>
        </article>
        <article class="card">
            <h2>Temperatura CPU (°C)</h2>
            <p class="value" id="temp">{{ $data['cpu_temp_c'] !== null ? $data['cpu_temp_c'].' °C' : 'No disponible' }}</p>
        </article>
        <article class="card">
            <h2>Encendido hace (segundos)</h2>
            <p class="value" id="uptime">{{ $data['uptime_s'] !== null ? $data['uptime_s'].' s' : 'No disponible' }}</p>
        </article>
    </section>
</main>

<script>
    async function actualizar() {
        try {
            const response = await fetch('/api/metrics');
            if (!response.ok) {
                throw new Error(`Error HTTP ${response.status}`);
            }

            const d = await response.json();
            const mostrar = (valor, unidad = '') =>
                valor === null || valor === undefined ? 'No disponible' : `${valor}${unidad}`;

            document.getElementById('at').textContent = d.at ?? 'No disponible';
            document.getElementById('disk').textContent = mostrar(d.disk?.used_pct, '%');
            document.getElementById('memory').textContent = mostrar(d.memory?.used_pct, '%');
            document.getElementById('cpu').textContent = mostrar(d.cpu_pct, '%');
            document.getElementById('temp').textContent = mostrar(d.cpu_temp_c, ' °C');
            document.getElementById('uptime').textContent = mostrar(d.uptime_s, ' s');
        } catch (error) {
            console.error('No se pudieron actualizar las métricas:', error);
        }
    }

    setInterval(actualizar, 5000);
</script>
</body>
</html>
