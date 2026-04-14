<style>
    .header { text-align: center; border-bottom: 2px solid #000; margin-bottom: 20px; }
    .content { font-size: 12pt; line-height: 1.5; }
    .signature { margin-top: 50px; text-align: center; }
</style>

<div class="header">
    <h2>CONSEJO COMUNAL "EMPRENDEDORES DE LOS PRÓCERES II ETAPA"</h2>
    <p>Ciudad Bolívar, Estado Bolívar - Rif: J-XXXXXXXXX</p>
</div>

<div class="content">
    <p style="text-align: right;">Fecha: <?php echo date('d/m/Y'); ?></p>
    
    <h3 style="text-align: center;">CONSTANCIA DE RESIDENCIA</h3>

    <p>Quien suscribe, Vocería Ejecutiva del Consejo Comunal, hace constar que el ciudadano(a):</p>
    <p><strong><?php echo $person['name_person']; ?></strong>, portador de la Cédula de Identidad <strong>V-<?php echo $person['ci_person']; ?></strong>.</p>
    
    <p>Reside en esta comunidad, específicamente en:</p>
    <ul>
        <li><strong>Calle:</strong> <?php echo $person['name_street']; ?></li>
        <li><strong>Manzana:</strong> <?php echo $person['codigo_square']; ?></li>
        <li><strong>Casa Nro:</strong> <?php echo $person['number_house']; ?></li>
    </ul>
</div>

<div class="signature">
    <p>__________________________</p>
    <p>Vocero Responsable</p>
</div>