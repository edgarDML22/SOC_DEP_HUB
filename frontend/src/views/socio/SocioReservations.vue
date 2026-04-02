<script setup>
import { useProfileStore } from '@/stores/profileStore';
import { IconUser, IconCalendar } from '@/components/icons';
const profileStore = useProfileStore();


const crearReservacion = async () => {
  try {
    const res = await fetch('http://localhost:8000/api/reservations', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        fechaHora: fechaHora.value,
        espacio_id: espacioSeleccionado.value
      })
    })

    const data = await res.json()
    console.log(data)

  } catch (error) {
    console.error(error)
  }
}

</script>

<template>
  <main class="main-content">
    <div class="Date-container">

      <div class="page-header">
        <h1 class="page-title">Reservar espacio</h1>
       
      </div>

      <div class="Date-content">

        <div class="profile-card details-card">
          <h3 class="card-title">Fecha</h3>

          <div class="form-container">

            <div class="form-group-with-icon">
              <div class="icon-box">
                <IconCalendar />
              </div>
              <div class="input-wrapper">
                <label for="fecha">Fecha</label>
                <input id="fecha" type="date" />
              </div>
            </div>
            
          </div>
        </div>
        <div class="profile-card details-card">
          <h3 class="card-title">Espacio</h3>

          <div class="form-container">

            <div class="form-group-with-icon">
              <div class="icon-box">
                <IconUser />
              </div>
              <div class="input-wrapper">
                <select>
                  <option value="">Futbol</option>
                  <option value="">Tenis</option>
                  <option value="">Basquetbol</option>
                </select>
              </div>
            </div>
            
          </div>
          
        </div>

      </div>
      
    </div>
    <div class="btn-container"><button class="btn-primary">Reservar</button></div>
    
  </main>
</template>



<style scoped>
.main-content {
  padding: 2rem;
}

.Date-container {
  max-width: 900px;
  margin: 0 auto;
}

.page-header {
  margin-bottom: 24px;
}

.page-title {
  font-size: 24px;
  font-weight: 700;
  margin: 0 0 4px 0;
  color: #111827;
}

.page-subtitle {
  font-size: 14px;
  color: #6b7280;
  margin: 0;
}

/* CARDS GENERAL */
.profile-card {
  background: #ffffff;
  border: 1px solid #e5e7eb;
  border-radius: 12px;
  padding: 24px;
  margin-bottom: 20px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

/* TARJETA 1: RESUMEN */
.Date-content {
  justify-content: center;
  display: flex;
  gap: 24px;
  width: 100%;
}

.summary-card {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.summary-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.avatar-large {
  width: 64px;
  height: 64px;
  background-color: #1d4ed8;
  color: white;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 20px;
  font-weight: 600;
}

.summary-text h2 {
  margin: 0 0 8px 0;
  font-size: 18px;
  font-weight: 600;
}

.badges-container {
  display: flex;
  gap: 8px;
}

.badge {
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 500;
  display: inline-block;
}

.badge-green {
  background-color: #dcfce7;
  color: #166534;
}

.badge-gray {
  background-color: #f3f4f6;
  color: #374151;
}



.edit-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  background-color: #ffffff;
  border: 1px solid #d1d5db;
  color: #374151;
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.2s;
}

.edit-btn:hover {
  background-color: #f9fafb;
}
s
.edit-btn svg {
  width: 16px;
  height: 16px;
}

.btn-container {
  display: flex;
  justify-content: center;
  margin-top: 20px;
}

.btn-primary {
  position: relative;
  justify-content: center;
  background-color: #1d4ed8;
  color: white;
  border: none;
  padding: 12px 24px;
  border-radius: 8px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.2s;
}

.btn-primary:hover {
  background-color: #1e40af;
}
/* TARJETA 2: DETALLES CON ICONOS */
.card-title {
  font-size: 16px;
  font-weight: 600;
  margin: 0 0 20px 0;
  color: #111827;
}

.form-container {
  display: flex;
  gap: 24px;
}

.form-group-with-icon {
  display: flex;
  align-items: center;
  gap: 16px;
}

.icon-box {
  width: 44px;
  height: 44px;
  background-color: #f3f4f6;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #6b7280;
  flex-shrink: 0;
}

.icon-box svg {
  width: 20px;
  height: 20px;
}

.input-wrapper {
  flex-grow: 1;
}

.input-wrapper label {
  display: block;
  font-weight: 500;
  margin-bottom: 0.4rem;
  font-size: 13px;
  color: #6b7280;
}

.input-wrapper input[type="text"] {
  width: 100%;
  padding: 10px 14px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background-color: #f9fafb;
  color: #111827;
  font-size: 15px;
  font-weight: 500;
  cursor: not-allowed;
  box-sizing: border-box;
}



.loading {
  text-align: center;
  padding: 40px;
  color: #6b7280;
}


</style>