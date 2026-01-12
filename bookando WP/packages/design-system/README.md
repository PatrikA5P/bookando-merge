# @bookando/design-system

Shared UI components and design system for the Bookando platform.

## 🚧 Status

This package is currently being set up. The 54+ Vue 3 components from `src/Core/Design` will be migrated here.

## 📦 Planned Structure

```
@bookando/design-system/
├── vue/                    # Vue 3 Components
│   ├── AppButton.vue
│   ├── AppModal.vue
│   ├── AppCard.vue
│   └── ... 50+ more
├── styles/                 # Shared SCSS/CSS
│   ├── variables.scss
│   ├── mixins.scss
│   └── themes/
└── tokens/                 # Design Tokens
    ├── colors.ts
    ├── spacing.ts
    └── typography.ts
```

## 🎯 Goals

1. **Component Migration** - Move all 54+ components from `src/Core/Design` to this package
2. **Framework Agnostic** - Consider Web Components for React Native compatibility
3. **Type-Safe** - Full TypeScript support with prop types
4. **Themeable** - Support for light/dark themes and custom branding
5. **Accessible** - WCAG 2.1 AA compliant

## 📝 Next Steps

- [ ] Setup Vite build for Vue components
- [ ] Migrate components from `src/Core/Design/components`
- [ ] Extract SCSS variables to design tokens
- [ ] Create Storybook for component documentation
- [ ] Add unit tests for all components

## 📄 License

Proprietary - Bookando Team
