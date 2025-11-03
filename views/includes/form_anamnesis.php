<?php
// Este archivo es un "componente" que se incluye en nueva_historia.php
// Contiene el formulario para la Anamnesis (Motivo, Enfermedad Actual, Antecedentes)
?>

<div class="card card-info">
    <div class="card-header">
        <h3 class="card-title">Información Clínica de la Consulta Actual</h3>
    </div>
    <div class="card-body">
         <div class="form-group">
            <label for="motivo_consulta">Motivo de consulta</label>
            <textarea class="form-control" rows="3" id="motivo_consulta" name="motivo_consulta"></textarea>
        </div>
        <div class="form-group">
            <label for="enfermedad_actual">Enfermedad actual</label>
            <textarea class="form-control" rows="3" id="enfermedad_actual" name="enfermedad_actual"></textarea>
        </div>
        <div class="form-group">
            <label for="antecedentes_personales">Antecedentes Patológicos Personales</label>
            <textarea class="form-control" rows="3" id="antecedentes_personales" name="antecedentes_personales"></textarea>
        </div>
        <div class="form-group">
            <label for="antecedentes_familiares">Antecedentes Patológicos Familiares</label>
            <textarea class="form-control" rows="3" id="antecedentes_familiares" name="antecedentes_familiares"></textarea>
        </div>
    </div></div>