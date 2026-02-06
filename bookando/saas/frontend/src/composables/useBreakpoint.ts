/**
 * useBreakpoint — Responsive Composable
 *
 * Reactive Breakpoint-Erkennung für Komponenten.
 * Ermöglicht conditional Rendering basierend auf Bildschirmgrösse.
 *
 * Verwendung:
 *   const { isMobile, isTablet, isDesktop, isLandscape } = useBreakpoint();
 *
 *   <div v-if="isMobile">Mobile-Ansicht</div>
 *   <div v-else>Desktop-Ansicht</div>
 */
import { ref, onMounted, onUnmounted, computed } from 'vue';
import { BREAKPOINTS } from '@/design';

export function useBreakpoint() {
  const width = ref(typeof window !== 'undefined' ? window.innerWidth : 1024);
  const height = ref(typeof window !== 'undefined' ? window.innerHeight : 768);

  function onResize() {
    width.value = window.innerWidth;
    height.value = window.innerHeight;
  }

  onMounted(() => {
    window.addEventListener('resize', onResize, { passive: true });
  });

  onUnmounted(() => {
    window.removeEventListener('resize', onResize);
  });

  // Breakpoint Flags
  const isXs = computed(() => width.value < BREAKPOINTS.sm);
  const isSm = computed(() => width.value >= BREAKPOINTS.sm && width.value < BREAKPOINTS.md);
  const isMd = computed(() => width.value >= BREAKPOINTS.md && width.value < BREAKPOINTS.lg);
  const isLg = computed(() => width.value >= BREAKPOINTS.lg && width.value < BREAKPOINTS.xl);
  const isXl = computed(() => width.value >= BREAKPOINTS.xl && width.value < BREAKPOINTS['2xl']);
  const is2xl = computed(() => width.value >= BREAKPOINTS['2xl']);

  // Convenience Flags
  const isMobile = computed(() => width.value < BREAKPOINTS.md);
  const isTablet = computed(() => width.value >= BREAKPOINTS.md && width.value < BREAKPOINTS.xl);
  const isDesktop = computed(() => width.value >= BREAKPOINTS.xl);
  const isLandscape = computed(() => width.value > height.value);
  const isPortrait = computed(() => height.value > width.value);

  // Touch Detection
  const isTouch = computed(() => {
    if (typeof window === 'undefined') return false;
    return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
  });

  // Current Breakpoint Name
  const current = computed(() => {
    if (is2xl.value) return '2xl';
    if (isXl.value) return 'xl';
    if (isLg.value) return 'lg';
    if (isMd.value) return 'md';
    if (isSm.value) return 'sm';
    return 'xs';
  });

  return {
    width,
    height,
    isXs,
    isSm,
    isMd,
    isLg,
    isXl,
    is2xl,
    isMobile,
    isTablet,
    isDesktop,
    isLandscape,
    isPortrait,
    isTouch,
    current,
  };
}
