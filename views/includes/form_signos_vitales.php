<?php
// Este archivo es un "componente" que se incluye en nueva_historia.php
// VERSIÓN 3: Reorganizado según la solicitud del 02-NOV-2025.
?>

<div class="card card-purple">
     <div class="card-header">
        <h3 class="card-title">Signos Vitales y Antropometría</h3>
    </div>
     <div class="card-body">
        
        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="peso_kg">Peso (kg)</label>
                    <input type="number" step="0.01" class="form-control" id="peso_kg" name="peso_kg">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="talla_cm">Talla (cm)</label>
                    <input type="number" step="0.01" class="form-control" id="talla_cm" name="talla_cm">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="imc">IMC</label>
                    <input type="text" class="form-control" id="imc" name="imc" readonly>
                    <span id="imc_clasificacion_alerta" class="badge mt-1" style="font-size: 0.9em; width: 100%;"></span>
                </div>
            </div>
        </div>
        
        <div class="row">
             <div class="col-md-4">
                <div class="form-group">
                    <label for="tension_sistolica">T.A. Sistólica (mmHg)</label>
                    <input type="number" class="form-control" id="tension_sistolica" name="tension_sistolica">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="tension_diastolica">T.A. Diastólica (mmHg)</label>
                    <input type="number" class="form-control" id="tension_diastolica" name="tension_diastolica">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label>Clasificación HTA</label>
                    <span id="hta_clasificacion_alerta" class="badge d-block p-2" style="font-size: 1.0em; margin-top: 5px;; width: 100%;"></span>
                </div>
            </div>
        </div>
         
         <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="frecuencia_cardiaca">Frec. Cardíaca (lat/min)</label>
                    <input type="number" class="form-control" id="frecuencia_cardiaca" name="frecuencia_cardiaca">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="frecuencia_respiratoria">Frec. Respiratoria (resp/min)</label>
                    <input type="number" class="form-control" id="frecuencia_respiratoria" name="frecuencia_respiratoria">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="temperatura_c">Temperatura (°C)</label>
                    <input type="number" step="0.01" class="form-control" id="temperatura_c" name="temperatura_c">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="form-group">
                    <label for="hemoglobina_glicosilada">Hb Glicosilada (HbA1c %)</label>
                    <input type="number" step="0.01" class="form-control" id="hemoglobina_glicosilada" name="hemoglobina_glicosilada">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="creatinina_serica">Creatinina Sérica (mg/dL)</label>
                    <input type="number" step="0.01" class="form-control" id="creatinina_serica" name="creatinina_serica">
                </div>
            </div>
            <div class="col-md-4">
                <div class="form-group">
                    <label for="filtrado_glomerular_ckd_epi">Filtrado Glomerular (CKD-EPI)</label>
                    <input type="text" class="form-control" id="filtrado_glomerular_ckd_epi" name="filtrado_glomerular_ckd_epi" readonly>
                    <span id="egfr_clasificacion_alerta" class="badge mt-1" style="font-size: 0.9em; width: 100%;"></span>
                </div>
            </div>
        </div>
        
    </div></div>