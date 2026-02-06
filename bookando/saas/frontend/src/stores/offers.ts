/**
 * Offers Store — Dienstleistungen, Pakete, Gutscheine & Dynamic Pricing
 *
 * Zentraler Zustand fuer das Angebote-Modul.
 * Alle Preise in Minor Units (Rappen/Cents).
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

// ============================================================================
// TYPES
// ============================================================================

export type ServiceType = 'SERVICE' | 'EVENT' | 'ONLINE_COURSE';
export type VoucherType = 'PERCENTAGE' | 'FIXED';
export type PricingRuleType = 'EARLY_BIRD' | 'LAST_MINUTE' | 'SEASONAL' | 'DEMAND' | 'AI';

export interface ServiceItem {
  id: string;
  title: string;
  description: string;
  type: ServiceType;
  categoryId: string;
  categoryName: string;
  priceMinor: number;
  salePriceMinor?: number;
  currency: string;
  duration: number; // minutes
  active: boolean;
  image?: string;
  tags: string[];
  pricingRuleId?: string;
}

export interface Category {
  id: string;
  name: string;
  description: string;
  image?: string;
  parentId?: string;
  sortOrder: number;
  serviceCount: number;
}

export interface Bundle {
  id: string;
  title: string;
  description: string;
  serviceIds: string[];
  totalPriceMinor: number;
  savingsMinor: number;
  active: boolean;
  image?: string;
}

export interface Voucher {
  id: string;
  code: string;
  type: VoucherType;
  value: number;
  maxUses: number;
  usedCount: number;
  expiresAt: string;
  active: boolean;
  minOrderMinor?: number;
}

export interface PricingRuleConditions {
  daysBeforeMin?: number;
  daysBeforeMax?: number;
  dateRange?: { start: string; end: string };
  timeRange?: { start: string; end: string };
}

export interface PricingRule {
  id: string;
  name: string;
  type: PricingRuleType;
  discountPercent: number;
  conditions: PricingRuleConditions;
  active: boolean;
}

// ============================================================================
// MOCK DATA
// ============================================================================

const MOCK_CATEGORIES: Category[] = [
  { id: 'cat-1', name: 'Haarpflege', description: 'Schnitt, Farbe & Styling', sortOrder: 1, serviceCount: 4 },
  { id: 'cat-2', name: 'Kosmetik', description: 'Gesichtsbehandlungen & Make-up', sortOrder: 2, serviceCount: 3 },
  { id: 'cat-3', name: 'Wellness', description: 'Massage & Entspannung', sortOrder: 3, serviceCount: 2 },
  { id: 'cat-4', name: 'Workshops', description: 'Kurse & Events', sortOrder: 4, serviceCount: 1 },
];

const MOCK_SERVICES: ServiceItem[] = [
  {
    id: 'svc-1', title: 'Damenhaarschnitt', description: 'Waschen, Schnitt und Styling', type: 'SERVICE',
    categoryId: 'cat-1', categoryName: 'Haarpflege', priceMinor: 8500, currency: 'CHF', duration: 60,
    active: true, tags: ['beliebt', 'premium'], pricingRuleId: 'pr-1',
  },
  {
    id: 'svc-2', title: 'Herrenhaarschnitt', description: 'Schnitt und Styling', type: 'SERVICE',
    categoryId: 'cat-1', categoryName: 'Haarpflege', priceMinor: 4500, currency: 'CHF', duration: 30,
    active: true, tags: ['beliebt'],
  },
  {
    id: 'svc-3', title: 'Balayage Coloration', description: 'Natuerliche Farbverlaeufe', type: 'SERVICE',
    categoryId: 'cat-1', categoryName: 'Haarpflege', priceMinor: 18000, salePriceMinor: 15000, currency: 'CHF', duration: 150,
    active: true, tags: ['premium', 'trend'],
  },
  {
    id: 'svc-4', title: 'Bartpflege & Rasur', description: 'Traditionelle Nassrasur und Bartformung', type: 'SERVICE',
    categoryId: 'cat-1', categoryName: 'Haarpflege', priceMinor: 3500, currency: 'CHF', duration: 30,
    active: true, tags: ['herren'],
  },
  {
    id: 'svc-5', title: 'Gesichtsbehandlung Classic', description: 'Reinigung, Peeling und Maske', type: 'SERVICE',
    categoryId: 'cat-2', categoryName: 'Kosmetik', priceMinor: 12000, currency: 'CHF', duration: 75,
    active: true, tags: ['wellness'],
  },
  {
    id: 'svc-6', title: 'Anti-Aging Treatment', description: 'Intensive Faltenbehandlung mit Hyaluron', type: 'SERVICE',
    categoryId: 'cat-2', categoryName: 'Kosmetik', priceMinor: 22000, currency: 'CHF', duration: 90,
    active: true, tags: ['premium', 'anti-aging'],
  },
  {
    id: 'svc-7', title: 'Braut-Make-up', description: 'Professionelles Make-up fuer den grossen Tag', type: 'SERVICE',
    categoryId: 'cat-2', categoryName: 'Kosmetik', priceMinor: 25000, currency: 'CHF', duration: 120,
    active: false, tags: ['hochzeit', 'premium'],
  },
  {
    id: 'svc-8', title: 'Hot-Stone Massage', description: '60 Minuten Entspannung mit warmen Steinen', type: 'SERVICE',
    categoryId: 'cat-3', categoryName: 'Wellness', priceMinor: 14000, currency: 'CHF', duration: 60,
    active: true, tags: ['wellness', 'entspannung'],
  },
  {
    id: 'svc-9', title: 'Aromatherapie', description: 'Ganzkoerpermassage mit aetherischen Oelen', type: 'SERVICE',
    categoryId: 'cat-3', categoryName: 'Wellness', priceMinor: 16000, salePriceMinor: 13500, currency: 'CHF', duration: 75,
    active: true, tags: ['wellness', 'aroma'],
  },
  {
    id: 'svc-10', title: 'Styling Workshop', description: 'Gruppen-Workshop: Styling fuer den Alltag', type: 'EVENT',
    categoryId: 'cat-4', categoryName: 'Workshops', priceMinor: 9500, currency: 'CHF', duration: 180,
    active: true, tags: ['workshop', 'gruppe'],
  },
];

const MOCK_BUNDLES: Bundle[] = [
  {
    id: 'bun-1', title: 'Wellness-Paket', description: 'Hot-Stone Massage + Gesichtsbehandlung',
    serviceIds: ['svc-8', 'svc-5'], totalPriceMinor: 23000, savingsMinor: 3000, active: true,
  },
  {
    id: 'bun-2', title: 'Braut-Paket Deluxe', description: 'Damenhaarschnitt + Balayage + Braut-Make-up',
    serviceIds: ['svc-1', 'svc-3', 'svc-7'], totalPriceMinor: 45000, savingsMinor: 6500, active: true,
  },
  {
    id: 'bun-3', title: 'Herren Komplett', description: 'Herrenhaarschnitt + Bartpflege',
    serviceIds: ['svc-2', 'svc-4'], totalPriceMinor: 7000, savingsMinor: 1000, active: true,
  },
];

const MOCK_VOUCHERS: Voucher[] = [
  { id: 'vou-1', code: 'WELCOME20', type: 'PERCENTAGE', value: 20, maxUses: 100, usedCount: 34, expiresAt: '2026-06-30', active: true },
  { id: 'vou-2', code: 'SUMMER10', type: 'FIXED', value: 1000, maxUses: 50, usedCount: 50, expiresAt: '2025-09-30', active: false },
  { id: 'vou-3', code: 'VIP50', type: 'FIXED', value: 5000, maxUses: 20, usedCount: 8, expiresAt: '2026-12-31', active: true, minOrderMinor: 10000 },
  { id: 'vou-4', code: 'FREUND15', type: 'PERCENTAGE', value: 15, maxUses: 200, usedCount: 67, expiresAt: '2026-03-31', active: true },
  { id: 'vou-5', code: 'NEUJAHR25', type: 'PERCENTAGE', value: 25, maxUses: 30, usedCount: 30, expiresAt: '2026-01-31', active: false },
];

const MOCK_PRICING_RULES: PricingRule[] = [
  {
    id: 'pr-1', name: 'Fruehbucher 14 Tage', type: 'EARLY_BIRD', discountPercent: 10, active: true,
    conditions: { daysBeforeMin: 14, daysBeforeMax: 60 },
  },
  {
    id: 'pr-2', name: 'Last Minute 24h', type: 'LAST_MINUTE', discountPercent: 15, active: true,
    conditions: { daysBeforeMin: 0, daysBeforeMax: 1 },
  },
  {
    id: 'pr-3', name: 'Wintersaison', type: 'SEASONAL', discountPercent: 20, active: false,
    conditions: { dateRange: { start: '2026-12-01', end: '2027-02-28' } },
  },
  {
    id: 'pr-4', name: 'Randzeiten-Rabatt', type: 'DEMAND', discountPercent: 12, active: true,
    conditions: { timeRange: { start: '08:00', end: '10:00' } },
  },
];

// ============================================================================
// STORE
// ============================================================================

export const useOffersStore = defineStore('offers', () => {
  // State
  const services = ref<ServiceItem[]>([...MOCK_SERVICES]);
  const categories = ref<Category[]>([...MOCK_CATEGORIES]);
  const bundles = ref<Bundle[]>([...MOCK_BUNDLES]);
  const vouchers = ref<Voucher[]>([...MOCK_VOUCHERS]);
  const pricingRules = ref<PricingRule[]>([...MOCK_PRICING_RULES]);
  const loading = ref(false);

  // Getters
  const activeServices = computed(() => services.value.filter(s => s.active));
  const inactiveServices = computed(() => services.value.filter(s => !s.active));
  const activeBundles = computed(() => bundles.value.filter(b => b.active));
  const activeVouchers = computed(() => vouchers.value.filter(v => v.active));
  const activePricingRules = computed(() => pricingRules.value.filter(r => r.active));

  const allTags = computed(() => {
    const tagSet = new Set<string>();
    services.value.forEach(s => s.tags.forEach(t => tagSet.add(t)));
    return Array.from(tagSet).sort();
  });

  function getServiceById(id: string): ServiceItem | undefined {
    return services.value.find(s => s.id === id);
  }

  function getCategoryById(id: string): Category | undefined {
    return categories.value.find(c => c.id === id);
  }

  function getServicesByCategory(categoryId: string): ServiceItem[] {
    return services.value.filter(s => s.categoryId === categoryId);
  }

  function getPricingRuleById(id: string): PricingRule | undefined {
    return pricingRules.value.find(r => r.id === id);
  }

  // Actions — Services
  function addService(service: Omit<ServiceItem, 'id'>) {
    const id = `svc-${Date.now()}`;
    services.value.push({ ...service, id });
    updateCategoryCount(service.categoryId);
    return id;
  }

  function updateService(id: string, updates: Partial<ServiceItem>) {
    const index = services.value.findIndex(s => s.id === id);
    if (index !== -1) {
      const oldCategoryId = services.value[index].categoryId;
      services.value[index] = { ...services.value[index], ...updates };
      if (updates.categoryId && updates.categoryId !== oldCategoryId) {
        updateCategoryCount(oldCategoryId);
        updateCategoryCount(updates.categoryId);
      }
    }
  }

  function deleteService(id: string) {
    const service = services.value.find(s => s.id === id);
    if (service) {
      services.value = services.value.filter(s => s.id !== id);
      updateCategoryCount(service.categoryId);
    }
  }

  function toggleServiceActive(id: string) {
    const service = services.value.find(s => s.id === id);
    if (service) {
      service.active = !service.active;
    }
  }

  // Actions — Categories
  function addCategory(category: Omit<Category, 'id' | 'serviceCount'>) {
    const id = `cat-${Date.now()}`;
    categories.value.push({ ...category, id, serviceCount: 0 });
    return id;
  }

  function updateCategory(id: string, updates: Partial<Category>) {
    const index = categories.value.findIndex(c => c.id === id);
    if (index !== -1) {
      categories.value[index] = { ...categories.value[index], ...updates };
    }
  }

  function deleteCategory(id: string) {
    categories.value = categories.value.filter(c => c.id !== id);
  }

  function updateCategoryCount(categoryId: string) {
    const cat = categories.value.find(c => c.id === categoryId);
    if (cat) {
      cat.serviceCount = services.value.filter(s => s.categoryId === categoryId).length;
    }
  }

  // Actions — Bundles
  function addBundle(bundle: Omit<Bundle, 'id'>) {
    const id = `bun-${Date.now()}`;
    bundles.value.push({ ...bundle, id });
    return id;
  }

  function updateBundle(id: string, updates: Partial<Bundle>) {
    const index = bundles.value.findIndex(b => b.id === id);
    if (index !== -1) {
      bundles.value[index] = { ...bundles.value[index], ...updates };
    }
  }

  function deleteBundle(id: string) {
    bundles.value = bundles.value.filter(b => b.id !== id);
  }

  function toggleBundleActive(id: string) {
    const bundle = bundles.value.find(b => b.id === id);
    if (bundle) {
      bundle.active = !bundle.active;
    }
  }

  // Actions — Vouchers
  function addVoucher(voucher: Omit<Voucher, 'id' | 'usedCount'>) {
    const id = `vou-${Date.now()}`;
    vouchers.value.push({ ...voucher, id, usedCount: 0 });
    return id;
  }

  function updateVoucher(id: string, updates: Partial<Voucher>) {
    const index = vouchers.value.findIndex(v => v.id === id);
    if (index !== -1) {
      vouchers.value[index] = { ...vouchers.value[index], ...updates };
    }
  }

  function deleteVoucher(id: string) {
    vouchers.value = vouchers.value.filter(v => v.id !== id);
  }

  function toggleVoucherActive(id: string) {
    const voucher = vouchers.value.find(v => v.id === id);
    if (voucher) {
      voucher.active = !voucher.active;
    }
  }

  // Actions — Pricing Rules
  function addPricingRule(rule: Omit<PricingRule, 'id'>) {
    const id = `pr-${Date.now()}`;
    pricingRules.value.push({ ...rule, id });
    return id;
  }

  function updatePricingRule(id: string, updates: Partial<PricingRule>) {
    const index = pricingRules.value.findIndex(r => r.id === id);
    if (index !== -1) {
      pricingRules.value[index] = { ...pricingRules.value[index], ...updates };
    }
  }

  function deletePricingRule(id: string) {
    pricingRules.value = pricingRules.value.filter(r => r.id !== id);
  }

  function togglePricingRuleActive(id: string) {
    const rule = pricingRules.value.find(r => r.id === id);
    if (rule) {
      rule.active = !rule.active;
    }
  }

  return {
    // State
    services,
    categories,
    bundles,
    vouchers,
    pricingRules,
    loading,

    // Getters
    activeServices,
    inactiveServices,
    activeBundles,
    activeVouchers,
    activePricingRules,
    allTags,

    // Lookups
    getServiceById,
    getCategoryById,
    getServicesByCategory,
    getPricingRuleById,

    // Service Actions
    addService,
    updateService,
    deleteService,
    toggleServiceActive,

    // Category Actions
    addCategory,
    updateCategory,
    deleteCategory,

    // Bundle Actions
    addBundle,
    updateBundle,
    deleteBundle,
    toggleBundleActive,

    // Voucher Actions
    addVoucher,
    updateVoucher,
    deleteVoucher,
    toggleVoucherActive,

    // Pricing Rule Actions
    addPricingRule,
    updatePricingRule,
    deletePricingRule,
    togglePricingRuleActive,
  };
});
