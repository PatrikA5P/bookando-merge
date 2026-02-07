/**
 * Offers Store — Refactored mit Domain-Types
 *
 * Nutzt die SOLL-Architektur-Typen aus types/domain/offers.ts.
 * Alle Preise in Minor Units (Rappen/Cents) als Integer.
 * Offer = ServiceOffer | EventOffer | OnlineCourseOffer.
 */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/utils/api';
import type {
  Offer,
  ServiceOffer,
  EventOffer,
  OnlineCourseOffer,
  OfferType,
  OfferCategory,
  OfferExtra,
  Bundle,
  Voucher,
  PricingRule,
  PricingRuleType,
  PricingRuleConditions,
  OfferResourceRequirement,
  VoucherDiscountType,
  VoucherCategory,
} from '@/types/domain/offers';
import { isServiceOffer, isEventOffer, isOnlineCourseOffer } from '@/types/domain/offers';

// Re-export types fuer einfachen Import aus Komponenten
export type {
  Offer, ServiceOffer, EventOffer, OnlineCourseOffer, OfferType,
  OfferCategory, OfferExtra, Bundle, Voucher, PricingRule, OfferResourceRequirement,
  PricingRuleType, PricingRuleConditions, VoucherDiscountType, VoucherCategory,
};
export { isServiceOffer, isEventOffer, isOnlineCourseOffer };

// ============================================================================
// STORE
// ============================================================================

export const useOffersStore = defineStore('offers', () => {
  // State
  const offers = ref<Offer[]>([]);
  const categories = ref<OfferCategory[]>([]);
  const extras = ref<OfferExtra[]>([]);
  const bundles = ref<Bundle[]>([]);
  const vouchers = ref<Voucher[]>([]);
  const pricingRules = ref<PricingRule[]>([]);
  const loading = ref(false);
  const error = ref<string | null>(null);

  // ========================================================================
  // FETCH
  // ========================================================================

  async function fetchOffers(): Promise<void> {
    try {
      const response = await api.get<{ data: Offer[] }>('/v1/offers', { per_page: 100 });
      offers.value = response.data;
    } catch (e: unknown) {
      error.value = e instanceof Error ? e.message : 'Angebote konnten nicht geladen werden';
    }
  }

  async function fetchCategories(): Promise<void> {
    try {
      const response = await api.get<{ data: OfferCategory[] }>('/v1/categories');
      categories.value = response.data;
    } catch (e: unknown) {
      error.value = e instanceof Error ? e.message : 'Kategorien konnten nicht geladen werden';
    }
  }

  async function fetchExtras(): Promise<void> {
    try {
      const response = await api.get<{ data: OfferExtra[] }>('/v1/extras');
      extras.value = response.data;
    } catch (e: unknown) {
      error.value = e instanceof Error ? e.message : 'Extras konnten nicht geladen werden';
    }
  }

  async function fetchBundles(): Promise<void> {
    try {
      const response = await api.get<{ data: Bundle[] }>('/v1/bundles');
      bundles.value = response.data;
    } catch (e: unknown) {
      error.value = e instanceof Error ? e.message : 'Pakete konnten nicht geladen werden';
    }
  }

  async function fetchVouchers(): Promise<void> {
    try {
      const response = await api.get<{ data: Voucher[] }>('/v1/vouchers', { per_page: 100 });
      vouchers.value = response.data;
    } catch (e: unknown) {
      error.value = e instanceof Error ? e.message : 'Gutscheine konnten nicht geladen werden';
    }
  }

  async function fetchPricingRules(): Promise<void> {
    try {
      const response = await api.get<{ data: PricingRule[] }>('/v1/pricing-rules');
      pricingRules.value = response.data;
    } catch (e: unknown) {
      error.value = e instanceof Error ? e.message : 'Preisregeln konnten nicht geladen werden';
    }
  }

  async function fetchAll(): Promise<void> {
    loading.value = true;
    error.value = null;
    try {
      await Promise.all([
        fetchOffers(),
        fetchCategories(),
        fetchExtras(),
        fetchBundles(),
        fetchVouchers(),
        fetchPricingRules(),
      ]);
    } finally {
      loading.value = false;
    }
  }

  // ========================================================================
  // GETTERS — Offers
  // ========================================================================

  const activeOffers = computed(() => offers.value.filter(o => o.status === 'ACTIVE'));
  const draftOffers = computed(() => offers.value.filter(o => o.status === 'DRAFT'));
  const archivedOffers = computed(() => offers.value.filter(o => o.status === 'ARCHIVED'));

  const serviceOffers = computed(() =>
    offers.value.filter((o): o is ServiceOffer => o.offerType === 'SERVICE')
  );
  const eventOffers = computed(() =>
    offers.value.filter((o): o is EventOffer => o.offerType === 'EVENT')
  );
  const onlineCourseOffers = computed(() =>
    offers.value.filter((o): o is OnlineCourseOffer => o.offerType === 'ONLINE_COURSE')
  );

  const activeBundles = computed(() => bundles.value.filter(b => b.active));
  const activeVouchers = computed(() => vouchers.value.filter(v => v.active));
  const activePricingRules = computed(() => pricingRules.value.filter(r => r.active));
  const activeExtras = computed(() => extras.value.filter(e => e.active));

  const allTags = computed(() => {
    const tagSet = new Set<string>();
    offers.value.forEach(o => o.tags.forEach(t => tagSet.add(t)));
    return Array.from(tagSet).sort();
  });

  // ========================================================================
  // LOOKUPS
  // ========================================================================

  function getOfferById(id: string): Offer | undefined {
    return offers.value.find(o => o.id === id);
  }

  function getCategoryById(id: string): OfferCategory | undefined {
    return categories.value.find(c => c.id === id);
  }

  function getOffersByCategory(categoryId: string): Offer[] {
    return offers.value.filter(o => o.categoryId === categoryId);
  }

  function getPricingRuleById(id: string): PricingRule | undefined {
    return pricingRules.value.find(r => r.id === id);
  }

  function getExtraById(id: string): OfferExtra | undefined {
    return extras.value.find(e => e.id === id);
  }

  // ========================================================================
  // OFFER ACTIONS
  // ========================================================================

  async function addOffer(offer: Omit<Offer, 'id' | 'organizationId' | 'createdAt' | 'updatedAt'>): Promise<string> {
    const response = await api.post<{ data: Offer }>('/v1/offers', offer);
    offers.value.push(response.data);
    return response.data.id;
  }

  async function updateOffer(id: string, updates: Partial<Offer>): Promise<void> {
    const response = await api.put<{ data: Offer }>(`/v1/offers/${id}`, updates);
    const index = offers.value.findIndex(o => o.id === id);
    if (index !== -1) {
      offers.value[index] = response.data;
    }
  }

  async function deleteOffer(id: string): Promise<void> {
    await api.delete(`/v1/offers/${id}`);
    offers.value = offers.value.filter(o => o.id !== id);
  }

  async function setOfferStatus(id: string, status: Offer['status']): Promise<void> {
    await api.patch<{ data: Offer }>(`/v1/offers/${id}/status`, { status });
    const offer = offers.value.find(o => o.id === id);
    if (offer) {
      offer.status = status;
    }
  }

  // ========================================================================
  // CATEGORY ACTIONS
  // ========================================================================

  async function addCategory(category: Omit<OfferCategory, 'id' | 'serviceCount'>): Promise<string> {
    const response = await api.post<{ data: OfferCategory }>('/v1/categories', category);
    categories.value.push(response.data);
    return response.data.id;
  }

  async function updateCategory(id: string, updates: Partial<OfferCategory>): Promise<void> {
    const response = await api.put<{ data: OfferCategory }>(`/v1/categories/${id}`, updates);
    const index = categories.value.findIndex(c => c.id === id);
    if (index !== -1) {
      categories.value[index] = response.data;
    }
  }

  async function deleteCategory(id: string): Promise<void> {
    await api.delete(`/v1/categories/${id}`);
    categories.value = categories.value.filter(c => c.id !== id);
  }

  // ========================================================================
  // EXTRA ACTIONS
  // ========================================================================

  async function addExtra(extra: Omit<OfferExtra, 'id'>): Promise<string> {
    const response = await api.post<{ data: OfferExtra }>('/v1/extras', extra);
    extras.value.push(response.data);
    return response.data.id;
  }

  async function updateExtra(id: string, updates: Partial<OfferExtra>): Promise<void> {
    const response = await api.put<{ data: OfferExtra }>(`/v1/extras/${id}`, updates);
    const index = extras.value.findIndex(e => e.id === id);
    if (index !== -1) {
      extras.value[index] = response.data;
    }
  }

  async function deleteExtra(id: string): Promise<void> {
    await api.delete(`/v1/extras/${id}`);
    extras.value = extras.value.filter(e => e.id !== id);
  }

  // ========================================================================
  // BUNDLE ACTIONS
  // ========================================================================

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

  // ========================================================================
  // VOUCHER ACTIONS
  // ========================================================================

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

  // ========================================================================
  // PRICING RULE ACTIONS
  // ========================================================================

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

  return {
    // State
    offers,
    categories,
    extras,
    bundles,
    vouchers,
    pricingRules,
    loading,
    error,

    // Fetch
    fetchAll,
    fetchOffers,
    fetchCategories,
    fetchExtras,
    fetchBundles,
    fetchVouchers,
    fetchPricingRules,

    // Getters — Offers
    activeOffers,
    draftOffers,
    archivedOffers,
    serviceOffers,
    eventOffers,
    onlineCourseOffers,
    activeBundles,
    activeVouchers,
    activePricingRules,
    activeExtras,
    allTags,

    // Lookups
    getOfferById,
    getCategoryById,
    getOffersByCategory,
    getPricingRuleById,
    getExtraById,

    // Offer Actions
    addOffer,
    updateOffer,
    deleteOffer,
    setOfferStatus,

    // Category Actions
    addCategory,
    updateCategory,
    deleteCategory,

    // Extra Actions
    addExtra,
    updateExtra,
    deleteExtra,

    // Bundle Actions
    addBundle,
    updateBundle,
    deleteBundle,

    // Voucher Actions
    addVoucher,
    updateVoucher,
    deleteVoucher,

    // Pricing Rule Actions
    addPricingRule,
    updatePricingRule,
    deletePricingRule,
  };
});
