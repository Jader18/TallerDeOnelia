<?php
require_once '../config/database.php';

$stmt = $pdo->prepare("
    SELECT id, nombre, descripcion, precio_base, duracion_horas
    FROM tipos_evento
    WHERE slug = ? AND activo = 1
");
$stmt->execute(['decoracion-empresarial']);  
$evento = $stmt->fetch();

if (!$evento) {
    die('Evento no encontrado o inactivo.');
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($evento['nombre']) ?> - El Taller de Onelia</title>
    <link rel="stylesheet" href="../assets/css/main.css">
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    
    <style>
        .evento-detalle {
            padding: 2rem 0 4rem;
        }
        
        .evento-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .evento-header h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            color: var(--primary);
            margin-bottom: 0.5rem;
            position: relative;
            display: inline-block;
        }
        
        .evento-header h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: 3px;
        }
        
        .evento-badge {
            display: inline-block;
            background: var(--secondary);
            color: white;
            padding: 0.3rem 1.5rem;
            border-radius: 50px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 0.5rem;
        }
        
        .evento-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
            margin: 3rem 0;
            align-items: start;
        }
        
        .evento-imagen-principal {
            position: relative;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
        }
        
        .evento-imagen-principal img {
            width: 100%;
            height: auto;
            aspect-ratio: 4/3;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .evento-imagen-principal:hover img {
            transform: scale(1.05);
        }
        
        .evento-imagen-principal::before {
            content: 'Foto de referencia';
            position: absolute;
            bottom: 1rem;
            right: 1rem;
            background: rgba(0,0,0,0.6);
            color: white;
            padding: 0.3rem 1rem;
            border-radius: 50px;
            font-size: 0.8rem;
            z-index: 2;
        }
        
        .evento-info-card {
            background: white;
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(200, 155, 123, 0.2);
        }
        
        .evento-descripcion {
            font-size: 1.1rem;
            line-height: 1.8;
            color: var(--text-dark);
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        
        .evento-detalles-lista {
            list-style: none;
            padding: 0;
            margin: 0 0 2rem 0;
        }
        
        .evento-detalles-lista li {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem 0;
            border-bottom: 1px dashed #eee;
        }
        
        .evento-detalles-lista li:last-child {
            border-bottom: none;
        }
        
        .detalle-icono {
            width: 40px;
            height: 40px;
            background: var(--bg-light);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-size: 1.2rem;
        }
        
        .detalle-contenido {
            flex: 1;
        }
        
        .detalle-contenido strong {
            display: block;
            color: var(--primary);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .detalle-contenido span {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
        }
        
        .precio-destacado {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            text-align: center;
            margin: 2rem 0 1.5rem;
        }
        
        .precio-destacado .label {
            font-size: 1rem;
            opacity: 0.9;
            display: block;
            margin-bottom: 0.5rem;
        }
        
        .precio-destacado .valor {
            font-size: 2.5rem;
            font-weight: 700;
            line-height: 1.2;
        }
        
        .precio-destacado .nota {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-top: 0.5rem;
        }
        
        .evento-acciones {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .evento-acciones .btn {
            flex: 1;
            min-width: auto;
            padding: 1rem;
        }
        
        .galeria-titulo {
            text-align: center;
            margin: 4rem 0 2rem;
            position: relative;
        }
        
        .galeria-titulo h2 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }
        
        .galeria-titulo p {
            color: var(--text-dark);
            opacity: 0.7;
            max-width: 600px;
            margin: 0 auto;
        }
        
        .galeria-adicional {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }
        
        .foto-card {
            background: white;
            border-radius: var(--radius-sm);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            position: relative;
            aspect-ratio: 1/1;
            cursor: pointer;
        }
        
        .foto-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: var(--shadow-lg);
            z-index: 5;
        }
        
        .foto-card img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: var(--transition);
        }
        
        .foto-card:hover img {
            transform: scale(1.1);
        }
        
        .foto-card::after {
            content: '🔍';
            position: absolute;
            bottom: 0.5rem;
            right: 0.5rem;
            background: rgba(255,255,255,0.9);
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition);
        }
        
        .foto-card:hover::after {
            opacity: 1;
        }
        
        .evento-faq {
            margin-top: 4rem;
            padding: 3rem;
            background: var(--bg-light);
            border-radius: var(--radius);
        }
        
        .evento-faq h3 {
            text-align: center;
            color: var(--primary);
            font-size: 1.8rem;
            margin-bottom: 2rem;
        }
        
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .faq-item {
            background: white;
            padding: 1.5rem;
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }
        
        .faq-item h4 {
            color: var(--primary);
            margin-bottom: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .faq-item p {
            color: var(--text-dark);
            opacity: 0.8;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .evento-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
            
            .evento-acciones {
                flex-direction: column;
            }
            
            .galeria-adicional {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .evento-faq {
                padding: 2rem 1.5rem;
            }
            
            .faq-grid {
                grid-template-columns: 1fr;
            }
            
            .precio-destacado .valor {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 480px) {
            .galeria-adicional {
                grid-template-columns: 1fr;
            }
            
            .evento-info-card {
                padding: 1.5rem;
            }
            
            .detalle-contenido span {
                font-size: 1rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../components/header.php'; ?>

    <main>
        <section class="evento-detalle">
            <div class="container">
                
                <div class="evento-header">
                    <h1><?= htmlspecialchars($evento['nombre']) ?></h1>
                    <span class="evento-badge">Eventos Corporativos</span>
                </div>
                
                <div class="evento-grid">
                    
                    <div class="evento-imagen-principal">
                        <img src="../assets/img/tipos-eventos/5.jpeg"
                             alt="<?= htmlspecialchars($evento['nombre']) ?>"
                             class="foto-principal">
                    </div>
                    
                    <div class="evento-info-card">
                        
                        <div class="evento-descripcion">
                            <?= nl2br(htmlspecialchars($evento['descripcion'] ?: 'Transformamos espacios corporativos para crear ambientes profesionales y acogedores. Ideal para lanzamientos de productos, convenciones, cenas de empresa y eventos de networking.')) ?>
                        </div>
                        
                        <ul class="evento-detalles-lista">
                            <li>
                                <div class="detalle-icono">💰</div>
                                <div class="detalle-contenido">
                                    <strong>Precio base</strong>
                                    <span>C$ <?= number_format($evento['precio_base'], 2, ',', '.') ?></span>
                                </div>
                            </li>
                            <li>
                                <div class="detalle-icono">⏱️</div>
                                <div class="detalle-contenido">
                                    <strong>Duración</strong>
                                    <span><?= $evento['duracion_horas'] ?> horas (aprox.)</span>
                                </div>
                            </li>
                            <li>
                                <div class="detalle-icono">🏢</div>
                                <div class="detalle-contenido">
                                    <strong>Incluye</strong>
                                    <span>Branding corporativo, centros de mesa elegantes, ambientación profesional</span>
                                </div>
                            </li>
                            <li>
                                <div class="detalle-icono">📊</div>
                                <div class="detalle-contenido">
                                    <strong>Tipos de eventos</strong>
                                    <span>Lanzamientos, conferencias, cenas de gala, team building</span>
                                </div>
                            </li>
                        </ul>
                        
                        <div class="precio-destacado">
                            <span class="label">Inversión desde</span>
                            <div class="valor">C$ <?= number_format($evento['precio_base'], 2, ',', '.') ?></div>
                            <span class="nota">*Precio base sujeto a cambios según requerimientos</span>
                        </div>
                        
                        <div class="evento-acciones">
                            <a href="../reservar.php?tipo=<?= $evento['id'] ?>" class="btn btn-reservar">Reservar ahora</a>
                            <a href="../#calendario" class="btn btn-secondary">Ver disponibilidad</a>
                        </div>
                        
                    </div>
                </div>
                
                <div class="galeria-titulo">
                    <h2>Galería de inspiración</h2>
                    <p>Eventos corporativos que hemos ambientado para diversas empresas</p>
                </div>
                
                <div class="galeria-adicional">
                    <div class="foto-card">
                        <img src="../assets/img/eventos/empresarial/empresarial1.jpg" alt="Evento empresarial 1" loading="lazy">
                    </div>
                    <div class="foto-card">
                        <img src="../assets/img/eventos/empresarial/empresarial2.jpg" alt="Evento empresarial 2" loading="lazy">
                    </div>
                    <div class="foto-card">
                        <img src="../assets/img/eventos/empresarial/empresarial3.jpg" alt="Evento empresarial 3" loading="lazy">
                    </div>
                    <div class="foto-card">
                        <img src="../assets/img/eventos/empresarial/empresarial4.jpg" alt="Evento empresarial 4" loading="lazy">
                    </div>
                    <div class="foto-card">
                        <img src="../assets/img/eventos/empresarial/empresarial5.jpg" alt="Evento empresarial 5" loading="lazy">
                    </div>
                </div>
                
                <div class="evento-faq">
                    <h3>Preguntas frecuentes para Eventos Empresariales</h3>
                    <div class="faq-grid">
                        <div class="faq-item">
                            <h4>🏢 ¿Qué tipo de eventos corporativos cubren?</h4>
                            <p>Lanzamientos de productos, convenciones, cenas de gala, eventos de fin de año, conferencias, workshops y actividades de team building.</p>
                        </div>
                        <div class="faq-item">
                            <h4>📋 ¿Incluyen branding personalizado?</h4>
                            <p>Sí, podemos incorporar la imagen corporativa de tu empresa en la decoración: logos, colores institucionales, y elementos de branding.</p>
                        </div>
                        <div class="faq-item">
                            <h4>💼 ¿Trabajan con presupuestos empresariales?</h4>
                            <p>Por supuesto, nos adaptamos a diferentes rangos presupuestarios y podemos crear propuestas personalizadas según tus necesidades.</p>
                        </div>
                        <div class="faq-item">
                            <h4>📅 ¿Con qué anticipación debo contactarlos?</h4>
                            <p>Recomendamos contactarnos con 2-3 meses de anticipación para eventos corporativos de gran envergadura.</p>
                        </div>
                    </div>
                </div>
                
            </div>
        </section>
    </main>

    <?php include '../components/footer.php'; ?>
    
</body>

</html>