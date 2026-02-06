/**
 * Offers Store — Dienstleistungen, Pakete, Gutscheine & Dynamic Pricing
 *
 * Zentraler Zustand fuer das Angebote-Modul.
 * Alle Preise in Minor Units (Rappen/Cents).
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';

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
// STORE
// ============================================================================

export const useOffersStore = defineStore('offers', () => {
  // State
  const services = ref<ServiceItem[]>([]);
  const categories = ref<Category[]>([]);
  const bundles = ref<Bundle[]>([]);
  const vouchers = ref<Voucher[]>([]);
  const pricingRules = ref<PricingRule[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // Fetch functions

  async function fetchServices(): Promise<void> {
    try {
      const response = await api.get<{ data: ServiceItem[] }>('/v1/services', { per_page: 100 });
      services.value = response.data;
    } catch (e: any) {
      error.value = e.message || 'Failed to load services';
    }
  }

  async function fetchCategories(): Promise<void> {
    try {
      const response = await api.get<{ data: Category[] }>('/v1/categories');
      categories.value = response.data;
    } catch (e: any) {
      error.value = e.message || 'Failed to load categories';
    }
  }

  async function fetchBundles(): Promise<void> {
    try {
      const response = await api.get<{ data: Bundle[] }>('/v1/bundles');
      bundles.value = response.data;
    } catch (e: any) {
      error.value = e.message || 'Failed to load bundles';
    }
  }

  async function fetchVouchers(): Promise<void> {
    try {
      const response = await api.get<{ data: Voucher[] }>('/v1/vouchers', { per_page: 100 });
      vouchers.value = response.data;
    } catch (e: any) {
      error.value = e.message || 'Failed to load vouchers';
    }
  }

  async function fetchPricingRules(): Promise<void> {
    try {
      const response = await api.get<{ data: PricingRule[] }>('/v1/pricing-rules');
      pricingRules.value = response.data;
    } catch (e: any) {
      error.value = e.message || 'Failed to load pricing rules';
    }
  }

  async function fetchAll(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await Promise.all([
        fetchServices(),
        fetchCategories(),
        fetchBundles(),
        fetchVouchers(),
        fetchPricingRules(),
      ]);
    } finally {
      loading.value = false;
    }
  }

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
  async function addService(service: Omit<ServiceItem, 'id'>): Promise<string> {
    const response = await api.post<{ data: ServiceItem }>('/v1/services', service);
    services.value.push(response.data);
    updateCategoryCount(response.data.categoryId);
    return response.data.id;
  }

  async function updateService(id: string, updates: Partial<ServiceItem>): Promise<void> {
    const response = await api.put<{ data: ServiceItem }>(`/v1/services/${id}`, updates);
    const index = services.value.findIndex(s => s.id === id);
    if (index !== -1) {
      const oldCategoryId = services.value[index].categoryId;
      services.value[index] = response.data;
      if (response.data.categoryId !== oldCategoryId) {
        updateCategoryCount(oldCategoryId);
        updateCategoryCount(response.data.categoryId);
      }
    }
  }

  async function deleteService(id: string): Promise<void> {
    const service = services.value.find(s => s.id === id);
    await api.delete(`/v1/services/${id}`);
    services.value = services.value.filter(s => s.id !== id);
    if (service) {
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
  async function addCategory(category: Omit<Category, 'id' | 'serviceCount'>): Promise<string> {
    const response = await api.post<{ data: Category }>('/v1/categories', category);
    categories.value.push(response.data);
    return response.data.id;
  }

  async function updateCategory(id: string, updates: Partial<Category>): Promise<void> {
    const response = await api.put<{ data: Category }>(`/v1/categories/${id}`, updates);
    const index = categories.value.findIndex(c => c.id === id);
    if (index !== -1) {
      categories.value[index] = response.data;
    }
  }

  async function deleteCategory(id: string): Promise<void> {
    await api.delete(`/v1/categories/${id}`);
    categories.value = categories.value.filter(c => c.id !== id);
  }

  function updateCategoryCount(categoryId: string) {
    const cat = categories.value.find(c => c.id === categoryId);
    if (cat) {
      cat.serviceCount = services.value.filter(s => s.categoryId === categoryId).length;
    }
  }

  // Actions — Bundles
  async function addBundle(bundle: Omit<Bundle, 'id'>): Promise<string> {
    const response = await api.post<{ data: Bundle }>('/v1/bundles', bundle);
    bundles.value.push(response.data);
    return response.data.id;
  }

  async function updateBundle(id: string, updates: Partial<Bundle>): Promise<void> {
    const response = await api.put<{ data: Bundle }>(`/v1/bundles/${id}`, updates);
    const index = bundles.value.findIndex(b => b.id === id);
    if (index !== -1) {
      bundles.value[index] = response.data;
    }
  }

  async function deleteBundle(id: string): Promise<void> {
    await api.delete(`/v1/bundles/${id}`);
    bundles.value = bundles.value.filter(b => b.id !== id);
  }

  function toggleBundleActive(id: string) {
    const bundle = bundles.value.find(b => b.id === id);
    if (bundle) {
      bundle.active = !bundle.active;
    }
  }

  // Actions — Vouchers
  async function addVoucher(voucher: Omit<Voucher, 'id' | 'usedCount'>): Promise<string> {
    const response = await api.post<{ data: Voucher }>('/v1/vouchers', voucher);
    vouchers.value.push(response.data);
    return response.data.id;
  }

  async function updateVoucher(id: string, updates: Partial<Voucher>): Promise<void> {
    const response = await api.put<{ data: Voucher }>(`/v1/vouchers/${id}`, updates);
    const index = vouchers.value.findIndex(v => v.id === id);
    if (index !== -1) {
      vouchers.value[index] = response.data;
    }
  }

  async function deleteVoucher(id: string): Promise<void> {
    await api.delete(`/v1/vouchers/${id}`);
    vouchers.value = vouchers.value.filter(v => v.id !== id);
  }

  function toggleVoucherActive(id: string) {
    const voucher = vouchers.value.find(v => v.id === id);
    if (voucher) {
      voucher.active = !voucher.active;
    }
  }

  // Actions — Pricing Rules
  async function addPricingRule(rule: Omit<PricingRule, 'id'>): Promise<string> {
    const response = await api.post<{ data: PricingRule }>('/v1/pricing-rules', rule);
    pricingRules.value.push(response.data);
    return response.data.id;
  }

  async function updatePricingRule(id: string, updates: Partial<PricingRule>): Promise<void> {
    const response = await api.put<{ data: PricingRule }>(`/v1/pricing-rules/${id}`, updates);
    const index = pricingRules.value.findIndex(r => r.id === id);
    if (index !== -1) {
      pricingRules.value[index] = response.data;
    }
  }

  async function deletePricingRule(id: string): Promise<void> {
    await api.delete(`/v1/pricing-rules/${id}`);
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
    error,

    // Fetch
    fetchAll,
    fetchServices,
    fetchCategories,
    fetchBundles,
    fetchVouchers,
    fetchPricingRules,

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
