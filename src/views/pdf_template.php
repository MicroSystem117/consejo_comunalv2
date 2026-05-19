<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Constancia de Residencia</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Arial', 'Helvetica', sans-serif;
            margin: 0;
            padding: 0;
        }
        html, body {
            margin: 0;
            padding: 0;
            background-color: #fff;
            color: #000;
            font-size: 10pt;
            line-height: 1.35;
        }
        body {
            padding: 0;
        }
        /* Contenedor principal que simula el recuadro de la hoja del CNE */
        .document-box {
            border: 1px solid #000;
            padding: 16px 14px;
            width: 100%;
            margin: 0 auto;
            position: relative;
        }
        /* Encabezado superior */
        .header-container {
            overflow: hidden;
            margin-bottom: 18px;
        }
        .header-left,
        .header-right {
            display: inline-block;
            vertical-align: top;
            width: 48%;
        }
        .header-left {
            font-size: 12pt;
            font-weight: bold;
            line-height: 1.1;
        }
        .header-left span {
            font-size: 7pt;
            font-weight: normal;
            display: block;
            letter-spacing: 1px;
            margin-top: 2px;
        }
        .header-right {
            text-align: right;
            font-size: 9pt;
            font-weight: bold;
            line-height: 1.2;
        }
        /* Título Central */
        .document-title {
            text-align: center;
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 24px;
            letter-spacing: 0.5px;
        }
        /* Contenido del texto */
        .content {
            font-size: 10pt;
            line-height: 1.4;
            text-align: justify;
            margin-bottom: 20px;
        }
        .content p {
            margin-bottom: 10px;
        }
        .data-list {
            list-style: none;
            margin: 12px 0 12px 16px;
            padding: 0;
        }
        .data-list li {
            margin-bottom: 6px;
        }
        /* Sección inferior de recepción/firmas */
        .footer-section {
            border-top: 1px dashed #000;
            margin-top: 30px;
            padding-top: 12px;
        }
        .footer-title {
            text-align: center;
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 14px;
        }
        .footer-title span {
            display: block;
            font-size: 8pt;
            font-weight: normal;
        }
        .receipt-data {
            font-size: 10pt;
            font-weight: bold;
            line-height: 1.3;
            margin-bottom: 16px;
        }
        /* Avisos e instrucciones finales */
        .notice-box {
            text-align: center;
            font-size: 9pt;
            font-weight: bold;
            line-height: 1.4;
            margin-top: 14px;
        }
        .notice-important {
            font-size: 8pt;
            margin-top: 8px;
        }
    </style>
</head>
<body>

<div class="document-box">
    
    <div class="header-container">
        <div class="header-left">
            CC
            <span>PODER POPULAR</span>
        </div>
        <div class="header-right">
            RIF: J-XXXXXXXXX<br>
            Formulario CC-PRÓCERES
        </div>
    </div>

    <div class="document-title">
        CONSTANCIA DE RESIDENCIA
    </div>

    <div class="content">
        <p><strong>REQUISITOS Y VALIDACIÓN:</strong></p>
        <p>La presente constancia es emitida por la Vocería Ejecutiva del Consejo Comunal <strong>"EMPRENDEDORES DE LOS PRÓCERES II ETAPA"</strong> de Ciudad Bolívar, Estado Bolívar, para dar fe y certificar la residencia del solicitante.</p>
        
        <p>Quien suscribe, hace constar que el ciudadano(a): <strong><?php echo htmlspecialchars($person['name_person']); ?></strong>, portador de la Cédula de Identidad número <strong>V-<?php echo htmlspecialchars($person['ci_person']); ?></strong>, habita en el ámbito geográfico de esta comunidad, específicamente en la siguiente dirección declarada:</p>
        
        <ul class="data-list">
            <li>- <strong>Calle / Avenida:</strong> <?php echo htmlspecialchars($person['name_street']); ?></li>
            <li>- <strong>Manzana / Sector:</strong> <?php echo htmlspecialchars($person['codigo_square']); ?></li>
            <li>- <strong>Número de Casa:</strong> <?php echo htmlspecialchars($person['number_house']); ?></li>
        </ul>

        <p><strong>Nota:</strong> Esta constancia tendrá plena validez para acreditar la residencia del ciudadano ante cualquier órgano público o privado que así lo requiera, de conformidad con las leyes de la República.</p>
    </div>

    <div class="footer-section">
        <div class="footer-title">
            CONSTANCIA DE RECEPCIÓN DE LA SOLICITUD
            <span>Solo para ser completado por la VOCERÍA / REGISTRO COMUNAL</span>
        </div>

        <div class="receipt-data">
            <p>Fecha de Recepción: _______________________</p>
            <p>Recibido por: ___________________________</p>
            <p>Se entregará en fecha: ____________________</p>
        </div>

        <div class="notice-box">
            <p>LA CONSTANCIA DE RESIDENCIA SE DEBE ENTREGAR EN UN LAPSO NO MAYOR A 3 DÍAS HÁBILES</p>
            <p class="notice-important">IMPORTANTE: IMPRIMA ESTE DOCUMENTO UNA SOLA VEZ</p>
        </div>
    </div>

</div>

</body>
</html>