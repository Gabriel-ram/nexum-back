# Sistema de Visitas de Perfiles - Guía para Frontend

## 📋 **Historia de Usuario Implementada:**

Como usuario profesional, quiero ver quién ha visitado mi perfil y cuántas veces ha sido visitado, para poder analizar el interés generado en mi portafolio.

---

## 🎯 **Endpoints Implementados:**

### **1. Registrar Visita**
**Endpoint:** `POST /api/v1/profile/{profileId}/visit`
- **Headers:** `Accept: application/json`
- **Body:** Ninguno (vacío)
- **Autenticación:** Opcional (si el visitante está logueado)

**Respuestas:**
- **201:** Visita registrada exitosamente
- **200 con `"Owner visit detected - not counted"`:** El dueño visitó su propio perfil (no se cuenta)
- **200 con `"Admin visit detected - not counted"`:** Un admin visitó (no se cuenta)
- **200 con `"Recent visit from same IP - not counted"`:** Misma IP en últimas 2 horas (previene spam)

### **2. Estadísticas del Perfil**
**Endpoint:** `GET /api/v1/profile/{profileId}/stats`
- **Headers:** `Accept: application/json`
- **Autenticación:** No requerida

**Response 200:**
```json
{
  "visits_count": 142,
  "recent_visitors": [
    { "user_id": 1, "name": "Juan Pérez", "visited_at": "2026-05-08T10:00:00" },
    { "user_id": 2, "name": "Ana López", "visited_at": "2026-05-07T18:30:00" },
    { "user_id": null, "name": "Visitante anónimo", "visited_at": "2026-05-07T15:45:00" }
    // ...hasta 5
  ]
}
```

### **3. Lista Completa de Visitantes**
**Endpoint:** `GET /api/v1/profile/{profileId}/visitors`
- **Headers:** `Accept: application/json`
- **Autenticación:** No requerida
- **Query Params:** `per_page` (opcional, default 20, max 100)

**Response 200:**
```json
{
  "data": [
    { "id": 1, "user_id": 3, "name": "Ana López", "visited_at": "2026-05-08T10:00:00" },
    { "id": 2, "user_id": null, "name": "Visitante anónimo", "visited_at": "2026-05-08T09:45:00" },
    { "id": 3, "user_id": 7, "name": "Carlos Ruiz", "visited_at": "2026-05-07T18:00:00" }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 1,
    "per_page": 20,
    "total": 3
  }
}
```

---

## 🔍 **Tipos de Visitantes Manejados:**

| Tipo | ¿Cuenta visita? | ¿Aparece en lista? | ¿Cómo se identifica? |
|-------|------------------|-------------------|-------------------|
| **Usuario registrado (no admin)** | ✅ Sí | ✅ Sí | Nombre completo del usuario |
| **Usuario no registrado / anónimo** | ✅ Sí | ✅ Sí | "Visitante anónimo" |
| **Admin** | ❌ No | ❌ No | Se excluye completamente |
| **Dueño del perfil** | ❌ No | ❌ No | Se excluye completamente |

---

## 🛡️ **Características de Seguridad:**

### **Prevención de Spam:**
- Las visitas de la misma IP no se cuentan si ocurren en menos de 2 horas
- Los admins nunca cuentan en las estadísticas
- Los dueños nunca cuentan sus propias visitas

### **Privacidad de Datos:**
- **Las IPs NUNCA se exponen al frontend**
- Los visitantes anónimos se muestran como "Visitante anónimo"
- Los usuarios registrados muestran su nombre completo

---

## 📊 **Estructura de Datos:**

### **Tabla `profile_visits`:**
```sql
- id (PK)
- portfolio_id (FK → portfolios.id)
- user_id (FK → users.id, nullable)
- ip_address (VARCHAR 45, nullable)
- visited_at (TIMESTAMP)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### **Relaciones en Modelo:**
- **Portfolio → ProfileVisit** (hasMany)
- **User → ProfileVisit** (hasMany)
- **ProfileVisit → Portfolio** (belongsTo)
- **ProfileVisit → User** (belongsTo)

---

## 🧪 **Flujo de Implementación Frontend:**

### **Para registrar una visita:**
```javascript
// Cuando un usuario visita un perfil público
fetch('{{base_url}}/api/v1/profile/{{portfolio_id}}/visit', {
  method: 'POST',
  headers: {
    'Accept': 'application/json',
    'Authorization': token ? `Bearer ${token}` : undefined
  }
})
.then(response => {
  if (response.status === 201) {
    // Visita registrada exitosamente
    console.log('Visita registrada');
  } else {
    // Manejar errores específicos
    console.log('Error:', response.data.message);
  }
});
```

### **Para mostrar estadísticas:**
```javascript
// Obtener estadísticas del perfil
fetch('{{base_url}}/api/v1/profile/{{portfolio_id}}/stats')
.then(response => response.json())
.then(data => {
  setVisitsCount(data.visits_count);
  setRecentVisitors(data.recent_visitors);
});
```

### **Para mostrar lista completa:**
```javascript
// Obtener lista paginada de visitantes
fetch('{{base_url}}/api/v1/profile/{{portfolio_id}}/visitors?per_page=20')
.then(response => response.json())
.then(data => {
  setVisitors(data.data);
  setPagination(data.meta);
});
```

---

## 🎯 **Casos de Uso:**

### **Caso 1: Visitante Anónimo (Sin Token)**
- **Request:** `POST /api/v1/profile/{id}/visit` sin headers de auth
- **IP:** 190.15.23.45
- **Resultado:** Se registra como anónimo, se muestra "Visitante anónimo"
- **Prevención duplicados:** Por IP en 2 horas
- **Response:** `"Recent visit from same IP - not counted"` (si duplicado)

### **Caso 2: Usuario Registrado (Con Token)**
- **Request:** `POST /api/v1/profile/{id}/visit` con `Authorization: Bearer {token}`
- **Usuario:** ana.garcia@portfolio.test (ID: 2)
- **Resultado:** Se registra con user_id: 2, se muestra "Ana García"
- **Prevención duplicados:** Por user_id en 2 horas (no por IP)
- **Response:** `"Recent visit from same user - not counted"` (si duplicado)

### **Caso 3: Admin Visitando**
- **Request:** `POST /api/v1/profile/{id}/visit` con token de admin
- **Usuario:** admin@portfolio.test (ID: 1)
- **Resultado:** No se registra, no aparece en estadísticas
- **Response:** `"Admin visit detected - not counted"`

### **Caso 4: Dueño Visitando**
- **Request:** `POST /api/v1/profile/{id}/visit` con token del dueño
- **Usuario:** carlos.mendez@portfolio.test (ID: 3) visitando su propio perfil (ID: 3)
- **Resultado:** No se registra, no aparece en estadísticas
- **Response:** `"Owner visit detected - not counted"`

### **Caso 5: Diferentes Usuarios con Misma IP**
- **Escenario:** Usuario A visita desde IP 190.15.23.45 → 201 Created
- **Escenario:** Usuario B visita desde misma IP con diferente token → 201 Created
- **Explicación:** Usuarios registrados se verifican por user_id, no por IP

### **Caso 6: Usuario Anónimo con IP Duplicada**
- **Escenario:** Visitante anónimo desde IP 190.15.23.45 → 201 Created
- **Escenario:** Mismo visitante anónimo 1 hora después → 200 con mensaje
- **Response:** `"Recent visit from same IP - not counted"`

### **Caso 7: Usuario Registrado con IP Duplicada**
- **Escenario:** Usuario A (ID: 2) visita → 201 Created
- **Escenario:** Mismo usuario A 1 hora después → 200 con mensaje
- **Response:** `"Recent visit from same user - not counted"`

---

## � **Respuestas Esperadas del API:**

### **POST /profile/{id}/visit**

#### **201 Created - Visita Registrada Exitosamente**
```json
{
  "message": "Visit recorded successfully",
  "data": {
    "id": 123,
    "portfolio_id": 1,
    "user_id": 2,
    "visited_at": "2026-05-08T22:30:00.000Z"
  }
}
```

#### **200 OK - Casos Especiales**
```json
// Admin visitando
{ "message": "Admin visit detected - not counted" }

// Dueño visitando  
{ "message": "Owner visit detected - not counted" }

// Usuario duplicado
{ "message": "Recent visit from same user - not counted" }

// IP duplicada (anónimo)
{ "message": "Recent visit from same IP - not counted" }
```

#### **404 Not Found**
```json
{
  "message": "Portfolio not found"
}
```

### **GET /profile/{id}/stats**

#### **200 OK - Estadísticas del Perfil**
```json
{
  "visits_count": 142,
  "recent_visitors": [
    { 
      "user_id": 2, 
      "name": "Ana García", 
      "visited_at": "2026-05-08T22:30:00.000Z" 
    },
    { 
      "user_id": null, 
      "name": "Visitante anónimo", 
      "visited_at": "2026-05-08T22:15:00.000Z" 
    }
  ]
}
```

### **GET /profile/{id}/visitors**

#### **200 OK - Lista Paginada**
```json
{
  "data": [
    {
      "id": 123,
      "user_id": 2,
      "name": "Ana García",
      "visited_at": "2026-05-08T22:30:00.000Z"
    },
    {
      "id": 124,
      "user_id": null,
      "name": "Visitante anónimo",
      "visited_at": "2026-05-08T22:15:00.000Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 20,
    "total": 56
  }
}
```

---

## 🎮 **Implementación Frontend - Ejemplos Prácticos:**

### **React Hook para Registrar Visita**
```javascript
const useProfileVisit = (portfolioId) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const recordVisit = async () => {
    setLoading(true);
    setError(null);

    try {
      const token = localStorage.getItem('auth_token');
      const headers = {
        'Accept': 'application/json',
      };

      // Agregar token solo si existe
      if (token) {
        headers['Authorization'] = `Bearer ${token}`;
      }

      const response = await fetch(
        `${process.env.REACT_APP_API_URL}/api/v1/profile/${portfolioId}/visit`,
        {
          method: 'POST',
          headers,
          body: JSON.stringify({})
        }
      );

      const data = await response.json();

      if (response.status === 201) {
        console.log('✅ Visita registrada:', data);
        return true;
      } else if (response.status === 200) {
        console.log('ℹ️ Caso especial:', data.message);
        return false; // No contada pero no es error
      } else {
        throw new Error(data.message || 'Error al registrar visita');
      }
    } catch (err) {
      setError(err.message);
      return false;
    } finally {
      setLoading(false);
    }
  };

  return { recordVisit, loading, error };
};
```

### **Componente de Estadísticas del Perfil**
```javascript
const ProfileStats = ({ portfolioId }) => {
  const [stats, setStats] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const fetchStats = async () => {
      try {
        const response = await fetch(
          `${process.env.REACT_APP_API_URL}/api/v1/profile/${portfolioId}/stats`,
          { headers: { 'Accept': 'application/json' } }
        );
        
        if (response.ok) {
          const data = await response.json();
          setStats(data);
        }
      } catch (error) {
        console.error('Error fetching stats:', error);
      } finally {
        setLoading(false);
      }
    };

    fetchStats();
  }, [portfolioId]);

  if (loading) return <div>Cargando estadísticas...</div>;
  if (!stats) return <div>No hay estadísticas disponibles</div>;

  return (
    <div className="profile-stats">
      <h3>📊 Estadísticas del Perfil</h3>
      <div className="visits-counter">
        <span className="number">{stats.visits_count}</span>
        <span className="label">visitas totales</span>
      </div>
      
      <div className="recent-visitors">
        <h4>Visitantes Recientes</h4>
        {stats.recent_visitors.map((visitor, index) => (
          <div key={index} className="visitor-item">
            <span className="visitor-name">
              {visitor.name}
            </span>
            <span className="visit-time">
              {new Date(visitor.visited_at).toLocaleString()}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
};
```

---

## �📝 **Notas Importantes:**

1. **El contador `views_count` del portfolio se incrementa automáticamente** con cada visita registrada
2. **Las IPs son privadas** - nunca se exponen al frontend por seguridad
3. **Los endpoints son públicos** - no requieren autenticación para las estadísticas
4. **La paginación funciona** igual que otros endpoints del sistema
5. **Todos los timestamps usan formato ISO 8601** para consistencia
6. **Los errores son descriptivos** pero no exponen información sensible
7. **La prevención de duplicados es inteligente** - por user_id para registrados, por IP para anónimos
8. **Los mensajes de respuesta son específicos** para cada caso especial

---

## 🚀 **Variables de Postman:**

- `{{portfolio_id}}` - ID del perfil a visitar/analizar
- `{{base_url}}` - URL base del servidor

---

**Implementación completa y lista para producción.**
