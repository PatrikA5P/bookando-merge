import { PrismaClient } from '@prisma/client';
import bcrypt from 'bcryptjs';

const prisma = new PrismaClient();

async function main() {
  console.log('🌱 Starting database seed...\n');

  // 1. Create License
  console.log('📝 Creating license...');
  const license = await prisma.license.create({
    data: {
      tier: 'PROFESSIONAL',
      status: 'ACTIVE',
      platforms: {
        web: true,
        mobile: true,
        wordpress: true,
      },
      enabledModules: {
        academy: { enabled: true, maxCourses: 100 },
        booking: { enabled: true, maxMonthlyBookings: 1000 },
        finance: { enabled: true },
        employees: { enabled: true, maxEmployees: 50 },
        resources: { enabled: true },
        notifications: { enabled: true },
        calendar: { enabled: true },
      },
      limits: {
        maxUsers: 50,
        maxCustomers: 1000,
        maxStorageGB: 10,
      },
      features: {
        customBranding: true,
        whiteLabel: false,
        apiAccess: true,
        sso: false,
        advancedReporting: true,
      },
      validFrom: new Date(),
      validUntil: new Date(Date.now() + 365 * 24 * 60 * 60 * 1000), // 1 year
      billingCycle: 'YEARLY',
      price: 1200,
      currency: 'CHF',
    },
  });

  // 2. Create Organization
  console.log('🏢 Creating organization...');
  const organization = await prisma.organization.create({
    data: {
      name: 'Demo Fahrschule Zürich',
      subdomain: 'demo',
      email: 'info@demo-fahrschule.ch',
      phone: '+41 44 123 45 67',
      address: 'Bahnhofstrasse 100',
      city: 'Zürich',
      zip: '8001',
      country: 'CH',
      language: 'de',
      timezone: 'Europe/Zurich',
      currency: 'CHF',
      taxRate: 7.7,
      licenseId: license.id,
    },
  });

  // 3. Create Admin Role
  console.log('👤 Creating admin role...');
  const adminRole = await prisma.role.create({
    data: {
      name: 'Administrator',
      permissions: {
        dashboard: { view: true },
        customers: { view: true, create: true, edit: true, delete: true },
        employees: { view: true, create: true, edit: true, delete: true },
        courses: { view: true, create: true, edit: true, delete: true },
        bookings: { view: true, create: true, edit: true, delete: true },
        finance: { view: true, create: true, edit: true, delete: true },
        resources: { view: true, create: true, edit: true, delete: true },
        settings: { view: true, edit: true },
        notifications: { view: true, edit: true },
        calendar: { view: true, edit: true },
      },
    },
  });

  // 4. Create Admin User
  console.log('🔐 Creating admin user...');
  const hashedPassword = await bcrypt.hash('Password123!', 10);
  const adminUser = await prisma.user.create({
    data: {
      email: 'admin@demo.ch',
      passwordHash: hashedPassword,
      firstName: 'Admin',
      lastName: 'User',
      organizationId: organization.id,
      roleId: adminRole.id,
      status: 'ACTIVE',
    },
  });

  // 5. Create Employee for Admin
  console.log('👨‍💼 Creating employee...');
  const employee = await prisma.employee.create({
    data: {
      organizationId: organization.id,
      userId: adminUser.id,
      position: 'Geschäftsführer',
      department: 'Management',
      hireDate: '2024-01-01',
      skills: ['Fahrunterricht', 'VKU', 'Prüfungsvorbereitung'],
      qualifications: ['Fahrlehrer Kat. B', 'Fahrlehrer Kat. A', 'VKU-Moderator'],
      workloadPercentage: 100,
      hourlyRate: 80.0,
      status: 'ACTIVE',
    },
  });

  // 6. Create Locations
  console.log('📍 Creating locations...');
  const mainLocation = await prisma.location.create({
    data: {
      organizationId: organization.id,
      name: 'Hauptstandort Zürich',
      address: 'Bahnhofstrasse 100',
      city: 'Zürich',
      zip: '8001',
      country: 'CH',
    },
  });

  const room1 = await prisma.room.create({
    data: {
      locationId: mainLocation.id,
      name: 'Theoriesaal 1',
      capacity: 20,
      equipment: ['Beamer', 'Whiteboard', 'WLAN'],
    },
  });

  const room2 = await prisma.room.create({
    data: {
      locationId: mainLocation.id,
      name: 'Prüfungsraum',
      capacity: 12,
      equipment: ['Computer', 'Prüfungssoftware'],
    },
  });

  // 7. Create Categories
  console.log('🏷️ Creating categories...');
  const theoryCategory = await prisma.category.create({
    data: {
      name: 'Theoriekurse',
      description: 'Alle Theorie- und VKU-Kurse',
      color: '#3B82F6',
      image: null,
    },
  });

  const practicalCategory = await prisma.category.create({
    data: {
      name: 'Fahrstunden',
      description: 'Praktische Fahrstunden',
      color: '#10B981',
      image: null,
    },
  });

  // 8. Create Tags
  console.log('🏷️ Creating tags...');
  const beginnersTag = await prisma.tag.create({
    data: {
      organizationId: organization.id,
      name: 'Anfänger',
      color: '#22C55E',
      description: 'Für Einsteiger geeignet',
    },
  });

  const intensiveTag = await prisma.tag.create({
    data: {
      organizationId: organization.id,
      name: 'Intensivkurs',
      color: '#F59E0B',
      description: 'Kompakter Intensivkurs',
    },
  });

  const onlineTag = await prisma.tag.create({
    data: {
      organizationId: organization.id,
      name: 'Online',
      color: '#3B82F6',
      description: 'Online durchführbar',
    },
  });

  // 9. Create Courses
  console.log('📚 Creating courses...');
  const vkuCourse = await prisma.course.create({
    data: {
      organizationId: organization.id,
      title: 'VKU Verkehrskunde Kurs',
      description: 'Obligatorischer Verkehrskundekurs für alle Führerscheinanwärter. In 4 Lektionen à 2 Stunden lernen Sie die wichtigsten Verhaltensregeln im Strassenverkehr.',
      coverImage: 'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=800',
      type: 'IN_PERSON',
      visibility: 'PUBLIC',
      status: 'ACTIVE',
      categoryId: theoryCategory.id,
      notifyParticipants: true,
      isRecurring: false,
      bookingStartsImmediately: true,
      bookingClosesOnStart: true,
      defaultLocationId: mainLocation.id,
      organizerId: employee.id,
      teamIds: [employee.id],
      price: 250.0,
      currency: 'CHF',
      depositEnabled: false,
      capacity: 20,
      allowGroupBooking: false,
      allowRepeatBooking: false,
      curriculum: {
        topics: [
          { title: 'Verkehrssehen', duration: 120, description: 'Sehen und gesehen werden' },
          { title: 'Verkehrsumwelt', duration: 120, description: 'Partnerkunde und Strassenkunde' },
          { title: 'Verkehrsdynamik', duration: 120, description: 'Geschwindigkeit und Fahrphysik' },
          { title: 'Verkehrstaktik', duration: 120, description: 'Fahrfähigkeit und Umweltbewusstsein' },
        ],
      },
      gallery: [],
      colorMode: 'preset',
      colorValue: '#3B82F6',
      certificate: true,
      certificateTemplate: 'VKU-Zertifikat',
      duration: 8,
      difficulty: 'Anfänger',
      showOnWebsite: true,
      published: true,
      waitlistEnabled: false,
      paymentLinkEnabled: false,
      paymentOnSite: true,
      googleMeetEnabled: false,
    },
  });

  // Link tags to VKU course
  await prisma.courseTag.createMany({
    data: [
      { courseId: vkuCourse.id, tagId: beginnersTag.id },
      { courseId: vkuCourse.id, tagId: intensiveTag.id },
    ],
  });

  const nothelferkurs = await prisma.course.create({
    data: {
      organizationId: organization.id,
      title: 'Nothelferkurs',
      description: 'Lebensrettende Sofortmassnahmen: Lernen Sie in 10 Stunden die wichtigsten Erste-Hilfe-Massnahmen. Obligatorisch für alle Führerscheinkategorien.',
      coverImage: 'https://images.unsplash.com/photo-1504813184591-01572f98c85f?w=800',
      type: 'IN_PERSON',
      visibility: 'PUBLIC',
      status: 'ACTIVE',
      categoryId: theoryCategory.id,
      notifyParticipants: true,
      isRecurring: false,
      bookingStartsImmediately: true,
      bookingClosesOnStart: true,
      defaultLocationId: mainLocation.id,
      organizerId: employee.id,
      teamIds: [employee.id],
      price: 150.0,
      currency: 'CHF',
      depositEnabled: true,
      depositAmount: 50.0,
      capacity: 12,
      allowGroupBooking: true,
      allowRepeatBooking: false,
      curriculum: {
        topics: [
          { title: 'Alarmierung und Sicherheit', duration: 120 },
          { title: 'Bewusstlosigkeit und Atmung', duration: 120 },
          { title: 'Herz-Kreislauf-Wiederbelebung', duration: 180 },
          { title: 'Blutungen und Wunden', duration: 120 },
          { title: 'Praktische Übungen', duration: 180 },
        ],
      },
      gallery: [],
      colorMode: 'preset',
      colorValue: '#EF4444',
      certificate: true,
      certificateTemplate: 'Nothelferkurs-Zertifikat',
      duration: 10,
      difficulty: 'Anfänger',
      showOnWebsite: true,
      published: true,
      waitlistEnabled: true,
      paymentLinkEnabled: false,
      paymentOnSite: true,
      googleMeetEnabled: false,
    },
  });

  await prisma.courseTag.create({
    data: { courseId: nothelferkurs.id, tagId: beginnersTag.id },
  });

  // 10. Create Course Sessions
  console.log('📅 Creating course sessions...');
  const today = new Date();
  const nextWeek = new Date(today);
  nextWeek.setDate(today.getDate() + 7);

  await prisma.courseSession.create({
    data: {
      courseId: vkuCourse.id,
      date: nextWeek.toISOString().split('T')[0],
      startTime: '18:00',
      endTime: '20:00',
      instructorId: employee.id,
      locationId: mainLocation.id,
      roomId: room1.id,
      maxParticipants: 20,
      currentEnrollment: 5,
      status: 'SCHEDULED',
    },
  });

  const twoWeeks = new Date(today);
  twoWeeks.setDate(today.getDate() + 14);

  await prisma.courseSession.create({
    data: {
      courseId: nothelferkurs.id,
      date: twoWeeks.toISOString().split('T')[0],
      startTime: '09:00',
      endTime: '17:00',
      instructorId: employee.id,
      locationId: mainLocation.id,
      roomId: room2.id,
      maxParticipants: 12,
      currentEnrollment: 3,
      status: 'SCHEDULED',
    },
  });

  // 11. Create Services
  console.log('🚗 Creating services...');
  const drivingLesson = await prisma.service.create({
    data: {
      organizationId: organization.id,
      name: 'Fahrstunde 45 Min',
      description: 'Standard Fahrstunde mit erfahrenem Fahrlehrer',
      category: 'DRIVING_LESSON',
      eventType: 'ONE_ON_ONE',
      assignmentStrategy: 'WORKLOAD_BALANCE',
      price: 90.0,
      currency: 'CHF',
      duration: 45,
      maxParticipants: 1,
      requiresLicense: false,
      isOnline: false,
      minNoticeHours: 24,
      maxAdvanceDays: 60,
      dynamicPricing: false,
      categoryId: practicalCategory.id,
      status: 'ACTIVE',
    },
  });

  // 12. Create Customers
  console.log('👥 Creating customers...');
  const customer1 = await prisma.customer.create({
    data: {
      organizationId: organization.id,
      firstName: 'Anna',
      lastName: 'Müller',
      email: 'anna.mueller@example.com',
      phone: '+41 79 123 45 67',
      address: 'Musterstrasse 10',
      zip: '8001',
      city: 'Zürich',
      country: 'CH',
      birthday: '2000-05-15',
      gender: 'Weiblich',
      status: 'ACTIVE',
    },
  });

  const customer2 = await prisma.customer.create({
    data: {
      organizationId: organization.id,
      firstName: 'Thomas',
      lastName: 'Schmidt',
      email: 'thomas.schmidt@example.com',
      phone: '+41 79 234 56 78',
      address: 'Hauptstrasse 25',
      zip: '8004',
      city: 'Zürich',
      country: 'CH',
      birthday: '1998-08-22',
      gender: 'Männlich',
      status: 'ACTIVE',
    },
  });

  // 13. Create Sample Enrollments
  console.log('✅ Creating enrollments...');
  await prisma.enrollment.create({
    data: {
      customerId: customer1.id,
      courseId: vkuCourse.id,
      progress: { completedLessons: [] },
      completed: false,
    },
  });

  await prisma.enrollment.create({
    data: {
      customerId: customer2.id,
      courseId: nothelferkurs.id,
      progress: { completedLessons: [] },
      completed: false,
    },
  });

  // 14. Create Sample Booking
  console.log('📅 Creating sample booking...');
  const tomorrow = new Date(today);
  tomorrow.setDate(today.getDate() + 1);

  await prisma.booking.create({
    data: {
      bookingNumber: 'BOOK-' + Date.now(),
      organizationId: organization.id,
      customerId: customer1.id,
      serviceId: drivingLesson.id,
      scheduledDate: tomorrow.toISOString().split('T')[0],
      scheduledTime: '14:00',
      participants: 1,
      notes: 'Erste Fahrstunde - Grundlagen',
      basePrice: 90.0,
      totalPrice: 90.0,
      employeeId: employee.id,
      status: 'CONFIRMED',
      paymentStatus: 'PENDING',
      extras: [],
      formResponses: [],
    },
  });

  console.log('\n✅ Seed completed successfully!\n');
  console.log('📊 Created:');
  console.log('  - 1 Organization (Demo Fahrschule Zürich)');
  console.log('  - 1 License (Professional)');
  console.log('  - 1 Admin User (admin@demo.ch / Password123!)');
  console.log('  - 1 Admin Role');
  console.log('  - 1 Employee (Max Muster)');
  console.log('  - 1 Location with 2 Rooms');
  console.log('  - 2 Categories');
  console.log('  - 3 Tags');
  console.log('  - 2 Courses (VKU, Nothelferkurs)');
  console.log('  - 2 Course Sessions');
  console.log('  - 1 Service (Fahrstunde)');
  console.log('  - 2 Customers');
  console.log('  - 2 Enrollments');
  console.log('  - 1 Sample Booking');
  console.log('\n🔐 Login credentials:');
  console.log('  Email: admin@demo.ch');
  console.log('  Password: Password123!');
  console.log('\n🚀 You can now start the backend with: npm run dev');
}

main()
  .catch((e) => {
    console.error('❌ Error during seed:', e);
    process.exit(1);
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
