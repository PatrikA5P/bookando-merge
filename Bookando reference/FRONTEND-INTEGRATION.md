# Frontend API-Integration

Diese Dokumentation erklärt, wie die Frontend-Module mit dem Backend verbunden sind.

## 🏗️ Architektur

```
/services/           # API Services (fetch/axios wrapper)
  ├── api.ts         # Zentrale API Client Konfiguration
  ├── auth.service.ts      # Authentication
  ├── course.service.ts    # Kurse/Academy
  └── customer.service.ts  # Kunden

/context/            # React Context für globalen State
  ├── AppContext.tsx       # App-weiter State
  └── AuthContext.tsx      # Authentication State

/hooks/              # Custom React Hooks
  ├── useCourses.ts        # Course Management Hook
  └── useCustomers.ts      # Customer Management Hook

/modules/            # UI Module
  ├── Academy.tsx          # Kurse & Academy
  ├── Customers.tsx        # Kundenverwaltung
  └── ...
```

## 🔐 Authentication

### Login verwenden

```typescript
import { useAuth } from '../context/AuthContext';

function LoginComponent() {
  const { login, isAuthenticated, user } = useAuth();

  const handleLogin = async () => {
    try {
      await login({
        email: 'admin@demo.ch',
        password: 'Password123!'
      });
      // User ist jetzt eingeloggt
      console.log('Logged in as:', user?.firstName);
    } catch (error) {
      console.error('Login failed:', error);
    }
  };

  return (
    <div>
      {isAuthenticated ? (
        <p>Willkommen {user?.firstName}!</p>
      ) : (
        <button onClick={handleLogin}>Login</button>
      )}
    </div>
  );
}
```

### Logout

```typescript
const { logout } = useAuth();

const handleLogout = async () => {
  await logout();
  // User ist ausgeloggt, Tokens gelöscht
};
```

## 📚 Kurse/Academy verwenden

### Mit dem Hook arbeiten

```typescript
import { useCourses } from '../hooks/useCourses';

function CoursesList() {
  const {
    courses,
    isLoading,
    error,
    createCourse,
    updateCourse,
    deleteCourse
  } = useCourses({
    published: true,  // Nur publizierte Kurse
    type: 'IN_PERSON'
  });

  if (isLoading) return <div>Lädt Kurse...</div>;
  if (error) return <div>Fehler: {error}</div>;

  return (
    <div>
      <h2>Kurse ({courses.length})</h2>
      {courses.map(course => (
        <div key={course.id}>
          <h3>{course.title}</h3>
          <p>{course.description}</p>
          <span>Preis: CHF {course.price}</span>
          {course.category && (
            <span>Kategorie: {course.category.name}</span>
          )}
        </div>
      ))}
    </div>
  );
}
```

### Neuen Kurs erstellen

```typescript
const { createCourse } = useCourses();

const handleCreateCourse = async () => {
  try {
    const newCourse = await createCourse({
      title: 'VKU Verkehrskunde',
      description: 'Obligatorischer Verkehrskundekurs',
      type: 'IN_PERSON',
      visibility: 'PUBLIC',
      price: 250,
      currency: 'CHF',
      capacity: 20,
      duration: 8,
      difficulty: 'Anfänger',
      published: false,
      showOnWebsite: true,
    });

    console.log('Kurs erstellt:', newCourse.id);
  } catch (error) {
    console.error('Fehler beim Erstellen:', error);
  }
};
```

### Kurs aktualisieren

```typescript
const { updateCourse } = useCourses();

const handleUpdateCourse = async (courseId: string) => {
  try {
    await updateCourse(courseId, {
      title: 'VKU Verkehrskunde - Neuer Titel',
      price: 280,
    });
  } catch (error) {
    console.error('Fehler beim Aktualisieren:', error);
  }
};
```

### Kurs veröffentlichen

```typescript
const { publishCourse } = useCourses();

const handlePublish = async (courseId: string) => {
  try {
    await publishCourse(courseId);
    // Kurs ist jetzt published=true
  } catch (error) {
    console.error('Fehler beim Veröffentlichen:', error);
  }
};
```

## 👥 Kunden verwenden

### Mit dem Hook arbeiten

```typescript
import { useCustomers } from '../hooks/useCustomers';

function CustomersList() {
  const {
    customers,
    isLoading,
    error,
    createCustomer,
    updateCustomer
  } = useCustomers({
    status: 'ACTIVE'
  });

  if (isLoading) return <div>Lädt Kunden...</div>;
  if (error) return <div>Fehler: {error}</div>;

  return (
    <div>
      <h2>Kunden ({customers.length})</h2>
      {customers.map(customer => (
        <div key={customer.id}>
          <h3>{customer.firstName} {customer.lastName}</h3>
          <p>Email: {customer.email}</p>
          <p>Telefon: {customer.phone}</p>
        </div>
      ))}
    </div>
  );
}
```

### Neuen Kunden erstellen

```typescript
const { createCustomer } = useCustomers();

const handleCreateCustomer = async () => {
  try {
    const newCustomer = await createCustomer({
      firstName: 'Max',
      lastName: 'Muster',
      email: 'max.muster@example.com',
      phone: '+41 79 123 45 67',
      address: 'Musterstrasse 1',
      zip: '8000',
      city: 'Zürich',
      country: 'CH',
      status: 'ACTIVE',
    });

    console.log('Kunde erstellt:', newCustomer.id);
  } catch (error) {
    console.error('Fehler beim Erstellen:', error);
  }
};
```

## 🔧 Direkte API-Calls (ohne Hook)

Falls Sie die Services direkt verwenden möchten:

```typescript
import courseService from '../services/course.service';
import customerService from '../services/customer.service';

// Kurse abrufen
const courses = await courseService.getCourses({ published: true });

// Einzelnen Kurs abrufen
const course = await courseService.getCourse('course-id');

// Kurs-Sessions abrufen
const sessions = await courseService.getCourseSessions('course-id');

// Tags abrufen
const tags = await courseService.getTags();

// Kunden abrufen
const customers = await customerService.getCustomers();
```

## 🎯 Integration in bestehende Module

### Academy.tsx aktualisieren

Ersetzen Sie den Mock-State durch echte API-Calls:

```typescript
// ALT (Mock-Daten):
const { courses, setCourses } = useApp();

// NEU (Echte API):
import { useCourses } from '../hooks/useCourses';

function AcademyModule() {
  const {
    courses,
    isLoading,
    error,
    createCourse,
    updateCourse,
    deleteCourse
  } = useCourses();

  // Verwenden Sie courses, createCourse, etc.
  // Die Daten kommen jetzt vom Backend!
}
```

### Customers.tsx aktualisieren

```typescript
// ALT (Mock-Daten):
const [customers, setCustomers] = useState(mockCustomers);

// NEU (Echte API):
import { useCustomers } from '../hooks/useCustomers';

function CustomersModule() {
  const {
    customers,
    isLoading,
    createCustomer,
    updateCustomer
  } = useCustomers();

  // Die Daten kommen jetzt vom Backend!
}
```

## 🚨 Fehlerbehandlung

Alle Services werfen strukturierte Fehler:

```typescript
try {
  await createCourse(data);
} catch (error) {
  if (error instanceof Error) {
    // error.message enthält die Fehlermeldung vom Server
    alert(`Fehler: ${error.message}`);
  }
}
```

Bei 401 Unauthorized wird der User automatisch ausgeloggt.

## 🔄 Token Refresh

Der API Client behandelt automatisch:
- Token aus localStorage laden
- Token in Header setzen
- Bei 401: User ausloggen und zu /login weiterleiten

## 📦 Environment Variables

Erstellen Sie eine `.env` Datei im Hauptverzeichnis:

```bash
VITE_API_URL=http://localhost:3001/api
```

Standard ist `http://localhost:3001/api` wenn nicht definiert.

## ✅ Checkliste für Module-Integration

1. ✅ Service erstellt (z.B. `course.service.ts`)
2. ✅ Custom Hook erstellt (z.B. `useCourses.ts`)
3. ⏳ Modul aktualisiert (Mock-Daten durch Hook ersetzen)
4. ⏳ Loading & Error States hinzufügen
5. ⏳ Forms mit echten API-Calls verbinden

## 🎨 UI States

Zeigen Sie immer Loading und Error States:

```typescript
const { courses, isLoading, error } = useCourses();

if (isLoading) {
  return <div className="flex items-center justify-center p-8">
    <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-brand-600"></div>
  </div>;
}

if (error) {
  return <div className="bg-red-50 border border-red-200 rounded-lg p-4 text-red-700">
    <strong>Fehler:</strong> {error}
  </div>;
}

// Normale Anzeige
return <div>...</div>;
```

## 🧪 Testen

### Backend muss laufen:
```bash
cd backend
npm run dev
```

### Frontend starten:
```bash
npm run dev
```

### Login testen:
- Email: `admin@demo.ch`
- Password: `Password123!`

### API direkt testen:
```bash
# Health Check
curl http://localhost:3001/health

# Kurse (ohne Auth)
curl http://localhost:3001/api/courses

# Mit Auth Token
curl -H "Authorization: Bearer YOUR_TOKEN" \
     http://localhost:3001/api/courses
```

## 📚 Weitere Services

Benötigen Sie weitere Services? Erstellen Sie nach diesem Pattern:

```typescript
// services/booking.service.ts
import apiClient from './api';

class BookingService {
  async getBookings() {
    return apiClient.get('/bookings');
  }

  async createBooking(data: any) {
    return apiClient.post('/bookings', data);
  }
}

export const bookingService = new BookingService();
```

## 🎯 Nächste Schritte

1. **Academy Modul**: Mock-Daten durch `useCourses` Hook ersetzen
2. **Customers Modul**: Mock-Daten durch `useCustomers` Hook ersetzen
3. **Dashboard**: Statistiken von echten APIs laden
4. **Booking Flow**: Buchungsformular mit Backend verbinden
5. **Employee Management**: Employee Service erstellen

---

**Bei Fragen:** Siehe `LOKALER-TEST.md` für Backend-Setup!
