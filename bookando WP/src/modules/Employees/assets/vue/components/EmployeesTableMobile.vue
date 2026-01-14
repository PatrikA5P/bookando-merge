<!-- EmployeesTableMobile.vue -->
<template>
  <div
    :class="[
      'bookando-container',
      'bookando-pl-none',
      'bookando-pr-none',
      'bookando-table-cards-context',
      'bookando-table-cards--md',
      containerClass
    ]"
  >
    <!-- ===== Sticky Header ===== -->
    <div class="bookando-table-cards-header">
      <div class="header-col header-checkbox">
        <AppCheckbox
          :model-value="allSelected"
          :aria-label="t('ui.a11y.select_all')"
          align="left"
          @update:model-value="toggleAll"
          @click.stop
        />
      </div>
      <div class="header-col header-title">
        {{ fieldLabel(t, 'employee', MODULE) }}
      </div>
      <div class="header-col header-actions">
        {{ t('core.common.actions') }}
      </div>
    </div>

    <!-- ===== Cards ===== -->
    <div class="bookando-table-cards">
      <div
        v-for="item in items"
        :key="item.id"
        class="bookando-table-card bookando-table-card--md bookando-table-card--comfy"
        :class="{
          'is-selected': selectedItems.includes(item.id),
          'is-expanded': expandedItems.has(item.id)
        }"
        @click="toggleExpand(item.id)"
      >
        <!-- Row-Top -->
        <div class="bookando-table-card__row bookando-table-card__row--top">
          <!-- Checkbox -->
          <div class="bookando-table-card__select">
            <AppCheckbox
              :model-value="selectedItems.includes(item.id)"
              :aria-label="t('ui.a11y.select_item')"
              align="left"
              @update:model-value="val => toggleItem(item.id, val)"
              @click.stop
            />
          </div>

          <!-- Mitarbeiterkarte (flat) -->
          <div class="bookando-table-card__main-left">
            <div class="bookando-card customer-identity-card customer-identity-card--flat">
              <div class="bookando-flex customer-identity-row">
                <!-- Avatar -->
                <div class="bookando-table-card__thumb">
                  <AppAvatar
                    :src="avatarSrc(item)"
                    :initials="initials(item)"
                    size="sm"
                    fit="cover"
                    :alt="`${item.first_name ?? ''} ${item.last_name ?? ''}`.trim()"
                    @error="e => onAvatarError(e, item)"
                  />
                </div>

                <!-- Daten (oben) -->
                <div class="bookando-table-card__meta">
                  <div class="title">
                    {{ item.first_name }} {{ item.last_name }}
                    <span class="bookando-text-muted bookando-text-sm"> (ID {{ item.id }})</span>
                  </div>

                  <div class="muted">
                    <AppIcon
                      name="mail"
                      class="bookando-icon bookando-mr-xxs"
                    />
                    <template v-if="item.email && String(item.email).trim()">
                      <a
                        :href="`mailto:${item.email}`"
                        class="bookando-link"
                        @click.stop
                      >{{ item.email }}</a>
                    </template>
                    <template v-else>
                      –
                    </template>
                  </div>

                  <div class="muted">
                    <AppIcon
                      name="phone"
                      class="bookando-icon bookando-mr-xxs"
                    />
                    <template v-if="item.phone && String(item.phone).trim()">
                      <a
                        :href="`tel:${normalizePhone(item.phone)}`"
                        class="bookando-link"
                        @click.stop
                      >{{ item.phone }}</a>
                    </template>
                    <template v-else>
                      –
                    </template>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Right: Actions oben, Chevron unten -->
          <div class="bookando-table-card__right">
            <div
              class="bookando-table-card__actions"
              @click.stop
            >
              <AppButton
                icon="edit"
                variant="standard"
                size="square"
                btn-type="icononly"
                icon-size="md"
                :tooltip="t('core.common.edit')"
                @click.stop="$emit('edit', item)"
              />
              <AppPopover
                trigger-mode="icon"
                trigger-icon="more-horizontal"
                trigger-variant="standard"
                :offset="2"
                width="content"
                :panel-min-width="220"
                panel-class="qa-menu"
                :close-on-item-click="true"
              >
                <template #content="{ close }">
                  <div
                    class="popover-menu"
                    role="none"
                  >
                    <template
                      v-for="opt in quickOptions"
                      :key="opt.value"
                    >
                      <div
                        class="dropdown-option"
                        role="menuitem"
                        :aria-disabled="opt.disabled ? 'true' : undefined"
                        :class="{ 'bookando-text-muted': opt.disabled }"
                        @click.stop="!opt.disabled && onQuickOption(opt.value, item, close)"
                      >
                        <AppIcon
                          :name="opt.icon"
                          class="dropdown-icon"
                          :class="opt.className"
                        />
                        <span class="option-label">{{ opt.label }}</span>
                      </div>
                      <div
                        v-if="opt.separatorAfter"
                        class="dropdown-separator"
                        aria-hidden="true"
                      />
                    </template>
                  </div>
                </template>
              </AppPopover>
            </div>

            <div
              class="bookando-table-card__chevron"
              aria-hidden="true"
            >
              <AppIcon
                :name="expandedItems.has(item.id) ? 'chevron-up' : 'chevron-down'"
                class="bookando-icon bookando-icon--md"
              />
            </div>
          </div>
        </div>

        <!-- Details -->
        <transition name="accordion-bounce">
          <div
            v-if="expandedItems.has(item.id)"
            class="bookando-table-card__details"
            @click.stop
          >
            <div class="bookando-card details-card">
              <div class="bookando-card__header">
                <AppTabs
                  :tabs="tabsDef"
                  :model-value="getDetailTab(item.id)"
                  content-padding="xxs"
                  @update:model-value="val => setDetailTab(item.id, val)"
                />
              </div>

              <div class="bookando-card__body">
                <div class="details-anim">
                  <transition
                    :name="panelTransition(item.id)"
                    mode="out-in"
                    @before-leave="onPanelBeforeLeave"
                    @enter="onPanelEnter"
                    @after-enter="onPanelAfterEnter"
                  >
                    <!-- =================== TAB: INFO =================== -->
                    <section
                      v-if="getDetailTab(item.id) === 'info'"
                      key="tab-info"
                    >
                      <div class="bookando-grid details-grid-2col">
                        <div class="bookando-table-detail">
                          <div
                            v-for="(field, idx) in infoFields"
                            :key="field.key + '-' + idx"
                            class="detail-row"
                          >
                            <div class="detail-label">
                              {{ field.label }}:
                            </div>
                            <div class="detail-value">
                              <template v-if="field.key === 'status'">
                                <span :class="['bookando-status-label', statusClass(item.status)]">
                                  {{ statusLabel(item.status, locale.value) }}
                                </span>
                              </template>

                              <template v-else-if="field.key === 'language' && item.language">
                                <span class="flag">{{ languageFlag(item.language) }}</span>
                                {{ languageLabel(item.language, locale.value) }}
                              </template>

                              <template v-else-if="field.key === 'country' && item.country">
                                <span class="flag">{{ countryFlag(item.country) }}</span>
                                {{ countryLabel(item.country, locale.value) || item.country }}
                              </template>

                              <template v-else-if="field.key === 'gender'">
                                {{ genderLabel(item.gender, locale.value) || '–' }}
                              </template>

                              <template v-else-if="field.key === 'birthdate'">
                                {{ formatDate(item.birthdate, locale.value) || '–' }}
                              </template>

                              <template v-else-if="field.key === 'email'">
                                <template v-if="item.email && String(item.email).trim()">
                                  <a
                                    :href="`mailto:${item.email}`"
                                    class="bookando-link"
                                  >{{ item.email }}</a>
                                </template>
                                <template v-else>
                                  –
                                </template>
                              </template>

                              <template v-else-if="field.key === 'phone'">
                                <template v-if="item.phone && String(item.phone).trim()">
                                  <a
                                    :href="`tel:${normalizePhone(item.phone)}`"
                                    class="bookando-link"
                                  >{{ item.phone }}</a>
                                </template>
                                <template v-else>
                                  –
                                </template>
                              </template>

                              <template v-else-if="field.key === 'work_locations'">
                                <span
                                  v-if="asArray(item.work_locations).length"
                                  class="bookando-text-sm"
                                >
                                  {{ chipsSummary(asArray(item.work_locations).map(l => l?.label || l), 2) }}
                                </span>
                                <span v-else>–</span>
                              </template>

                              <template v-else-if="['created_at','updated_at','deleted_at'].includes(field.key)">
                                {{ formatDatetime(item[field.key], locale.value) || '–' }}
                              </template>

                              <template v-else-if="field.key === 'badge'">
                                <span
                                  v-if="item.badge?.label"
                                  class="bookando-text-sm"
                                >{{ item.badge.label }}</span>
                                <span v-else>–</span>
                              </template>

                              <template v-else-if="field.key === 'address'">
                                <span class="bookando-text-sm">
                                  <template v-if="item.address">{{ item.address }}</template>
                                  <template v-if="item.address_2"><br>{{ item.address_2 }}</template>
                                  <template v-if="item.zip || item.city"><br>{{ [item.zip, item.city].filter(Boolean).join(' ') }}</template>
                                </span>
                                <span v-if="!item.address && !item.address_2 && !item.zip && !item.city">–</span>
                              </template>

                              <template v-else-if="field.key === 'description'">
                                <span class="bookando-text-sm bookando-text-muted">{{ item.description || '–' }}</span>
                              </template>

                              <template v-else-if="field.key === 'note'">
                                <span class="bookando-text-sm bookando-text-muted">{{ item.note || '–' }}</span>
                              </template>

                              <template v-else>
                                {{ item[field.key] ?? '–' }}
                              </template>
                            </div>
                          </div>
                        </div>
                      </div>
                    </section>

                    <!-- ================= TAB: ARBEITSZEITEN ================= -->
                    <section
                      v-else-if="getDetailTab(item.id) === 'workinghours'"
                      key="tab-workinghours"
                    >
                      <div class="bookando-table-detail">
                        <div
                          v-for="(day, di) in workingDaysFor(item)"
                          :key="day.key"
                          class="detail-row"
                        >
                          <div class="detail-label">
                            {{ day.label }}:
                          </div>

                          <div class="detail-value">
                            <template v-if="!day.summary.blocks.length">
                              <span class="bookando-text-muted">{{ t('mod.employees.label.closed') || 'Ruhetag' }}</span>
                            </template>

                            <template v-else>
                              <span class="bookando-text-sm">
                                {{ day.summary.blocks.map(b => `${b.start}–${b.end}`).join(', ') }}
                                • {{ day.summary.blockCount }} {{ t('mod.employees.form.working_days.blocks') || 'Blöcke' }}
                                • {{ day.summary.serviceCount }} {{ t('mod.employees.form.working_days.services') || 'Dienstleistungen' }}
                                • {{ day.summary.locationCount }} {{ t('mod.employees.form.working_days.locations') || 'Orte' }}
                              </span>

                              <a
                                href="#"
                                class="bookando-link bookando-ml-sm bookando-inline-flex bookando-items-center"
                                @click.prevent="toggleWorkdayDetails(item.id, di)"
                              >
                                <span>{{ isWorkdayOpen(item.id, di) ? (t('ui.common.hide_details') || 'Details ausblenden') : (t('ui.common.show_details') || 'Details anzeigen') }}</span>
                                <AppIcon
                                  :name="isWorkdayOpen(item.id, di) ? 'chevron-up' : 'chevron-right'"
                                  class="bookando-icon bookando-ml-xxs"
                                />
                              </a>

                              <transition name="panel-fade">
                                <div
                                  v-if="isWorkdayOpen(item.id, di)"
                                  class="bookando-mt-xxs"
                                >
                                  <div
                                    v-for="(combo, ci) in day.combos"
                                    :key="`c-${ci}`"
                                    class="bookando-text-sm bookando-mb-xxs"
                                  >
                                    <div>
                                      <strong>{{ t('mod.employees.form.working_days.working_hours') }}</strong>
                                      <span class="bookando-ml-xxs">{{ combo.work.map(w => `${w.start}–${w.end}`).join(', ') || '–' }}</span>
                                      <span
                                        v-if="combo.breaks?.length"
                                        class="bookando-text-muted"
                                      >
                                        • {{ t('mod.employees.form.working_days.breaks') }}:
                                        {{ combo.breaks.map(b => `${b.start}–${b.end}`).join(', ') }}
                                      </span>
                                    </div>
                                    <div class="bookando-text-muted">
                                      <strong>{{ t('mod.employees.form.working_days.services') }}:</strong>
                                      <span>{{ chipsSummary(combo.serviceLabels || [], 2) || '–' }}</span>
                                      • <strong>{{ t('mod.employees.form.working_days.locations') }}:</strong>
                                      <span>{{ chipsSummary(combo.locationLabels || [], 2) || '–' }}</span>
                                    </div>
                                  </div>
                                </div>
                              </transition>
                            </template>
                          </div>
                        </div>
                      </div>
                    </section>

                    <!-- =============== TAB: FREIE TAGE =============== -->
                    <section
                      v-else-if="getDetailTab(item.id) === 'days_off'"
                      key="tab-days-off"
                    >
                      <div class="bookando-table-detail">
                        <!-- Bevorstehend -->
                        <div class="bookando-mb-sm">
                          <div class="bookando-text-sm bookando-text-muted bookando-mb-xxs">
                            {{ t('mod.employees.form.days_off.upcoming') || t('core.common.upcoming') || 'Bevorstehend' }}
                          </div>

                          <template v-if="groupedDaysOff(item).upcoming.length">
                            <div
                              v-for="entry in visibleUpcoming(item)"
                              :key="entry._key"
                              class="detail-row"
                            >
                              <div class="detail-label">
                                {{ formatDate(entry.dateStart, locale.value) }}
                              </div>
                              <div class="detail-value">
                                <a
                                  href="#"
                                  class="bookando-link bookando-inline-flex bookando-items-center"
                                  @click.prevent="toggleDayOffDetails(item.id, entry._key)"
                                >
                                  <span>{{ formatRange(entry.dateStart, entry.dateEnd) }}</span>
                                  <AppIcon
                                    :name="isDayOffOpen(item.id, entry._key) ? 'chevron-up' : 'chevron-right'"
                                    class="bookando-icon bookando-ml-xxs"
                                  />
                                </a>

                                <transition name="panel-fade">
                                  <div
                                    v-if="isDayOffOpen(item.id, entry._key)"
                                    class="bookando-mt-xxs bookando-text-sm bookando-text-muted"
                                  >
                                    <div v-if="entry.title">
                                      <strong>{{ t('core.common.title') }}:</strong> {{ entry.title }}
                                    </div>
                                    <div v-if="entry.note">
                                      <strong>{{ t('core.common.note') }}:</strong> {{ entry.note }}
                                    </div>
                                    <div v-if="entry.repeatYearly">
                                      <span class="bookando-text-xs">{{ t('mod.employees.form.days_off.repeat_yearly') || 'Jährlich wiederholen' }}</span>
                                    </div>
                                  </div>
                                </transition>
                              </div>
                            </div>

                            <div
                              v-if="showMoreNeeded(item, 'up')"
                              class="bookando-mt-xxs"
                            >
                              <a
                                href="#"
                                class="bookando-link"
                                @click.prevent="toggleShowMore(item.id, 'up')"
                              >
                                {{ isShowingMore(item.id, 'up') ? (t('ui.common.show_less') || 'Weniger anzeigen') : (t('ui.common.show_more') || 'Mehr anzeigen') }}
                              </a>
                            </div>
                          </template>

                          <div
                            v-else
                            class="bookando-text-sm bookando-text-muted"
                          >
                            –
                          </div>
                        </div>

                        <!-- Vergangen -->
                        <div>
                          <div class="bookando-text-sm bookando-text-muted bookando-mb-xxs">
                            {{ t('mod.employees.form.days_off.past') || t('core.common.past') || 'Vergangen' }}
                          </div>

                          <template v-if="groupedDaysOff(item).past.length">
                            <div
                              v-for="entry in visiblePast(item)"
                              :key="entry._key"
                              class="detail-row"
                            >
                              <div class="detail-label">
                                {{ formatDate(entry.dateStart, locale.value) }}
                              </div>
                              <div class="detail-value">
                                <a
                                  href="#"
                                  class="bookando-link bookando-inline-flex bookando-items-center"
                                  @click.prevent="toggleDayOffDetails(item.id, entry._key)"
                                >
                                  <span>{{ formatRange(entry.dateStart, entry.dateEnd) }}</span>
                                  <AppIcon
                                    :name="isDayOffOpen(item.id, entry._key) ? 'chevron-up' : 'chevron-right'"
                                    class="bookando-icon bookando-ml-xxs"
                                  />
                                </a>

                                <transition name="panel-fade">
                                  <div
                                    v-if="isDayOffOpen(item.id, entry._key)"
                                    class="bookando-mt-xxs bookando-text-sm bookando-text-muted"
                                  >
                                    <div v-if="entry.title">
                                      <strong>{{ t('core.common.title') }}:</strong> {{ entry.title }}
                                    </div>
                                    <div v-if="entry.note">
                                      <strong>{{ t('core.common.note') }}:</strong> {{ entry.note }}
                                    </div>
                                    <div v-if="entry.repeatYearly">
                                      <span class="bookando-text-xs">{{ t('mod.employees.form.days_off.repeat_yearly') || 'Jährlich wiederholen' }}</span>
                                    </div>
                                  </div>
                                </transition>
                              </div>
                            </div>

                            <div
                              v-if="showMoreNeeded(item, 'pa')"
                              class="bookando-mt-xxs"
                            >
                              <a
                                href="#"
                                class="bookando-link"
                                @click.prevent="toggleShowMore(item.id, 'pa')"
                              >
                                {{ isShowingMore(item.id, 'pa') ? (t('ui.common.show_less') || 'Weniger anzeigen') : (t('ui.common.show_more') || 'Mehr anzeigen') }}
                              </a>
                            </div>
                          </template>

                          <div
                            v-else
                            class="bookando-text-sm bookando-text-muted"
                          >
                            –
                          </div>
                        </div>
                      </div>
                    </section>

                    <!-- ============== TAB: BESONDERE TAGE ============== -->
                    <section
                      v-else-if="getDetailTab(item.id) === 'special_days'"
                      key="tab-special"
                    >
                      <div class="bookando-table-detail">
                        <div
                          v-for="sd in specialSetsFor(item)"
                          :key="sd._key"
                          class="detail-row"
                        >
                          <div class="detail-label">
                            {{ formatDate(sd.start, locale.value) }}
                          </div>
                          <div class="detail-value">
                            <span class="bookando-text-sm">
                              {{ sd.summary.blocks.map(b => `${b.start}–${b.end}`).join(', ') || '–' }}
                              <template v-if="sd.summary.blocks.length">
                                • {{ sd.summary.blockCount }} {{ t('mod.employees.form.special_days.blocks') || 'Blöcke' }}
                                • {{ sd.summary.serviceCount }} {{ t('mod.employees.form.special_days.services') || 'Dienstleistungen' }}
                                • {{ sd.summary.locationCount }} {{ t('mod.employees.form.special_days.locations') || 'Orte' }}
                              </template>
                            </span>

                            <a
                              href="#"
                              class="bookando-link bookando-ml-sm bookando-inline-flex bookando-items-center"
                              @click.prevent="toggleSpecialSet(item.id, sd._key)"
                            >
                              <span>{{ isSpecialSetOpen(item.id, sd._key) ? (t('ui.common.hide_details') || 'Details ausblenden') : (t('ui.common.show_details') || 'Details anzeigen') }}</span>
                              <AppIcon
                                :name="isSpecialSetOpen(item.id, sd._key) ? 'chevron-up' : 'chevron-right'"
                                class="bookando-icon bookando-ml-xxs"
                              />
                            </a>

                            <transition name="panel-fade">
                              <div
                                v-if="isSpecialSetOpen(item.id, sd._key)"
                                class="bookando-mt-xxs"
                              >
                                <div
                                  v-for="(combo, ci) in sd.combos"
                                  :key="`sc-${ci}`"
                                  class="bookando-text-sm bookando-mb-xxs"
                                >
                                  <div>
                                    <strong>{{ t('mod.employees.form.special_days.working_hours') }}</strong>
                                    <span class="bookando-ml-xxs">{{ combo.work.map(w => `${w.start}–${w.end}`).join(', ') || '–' }}</span>
                                    <span
                                      v-if="combo.breaks?.length"
                                      class="bookando-text-muted"
                                    >
                                      • {{ t('mod.employees.form.special_days.breaks') }}:
                                      {{ combo.breaks.map(b => `${b.start}–${b.end}`).join(', ') }}
                                    </span>
                                  </div>
                                  <div class="bookando-text-muted">
                                    <strong>{{ t('mod.employees.form.special_days.services') }}:</strong>
                                    <span>{{ chipsSummary(combo.serviceLabels || [], 2) || '–' }}</span>
                                    • <strong>{{ t('mod.employees.form.special_days.locations') }}:</strong>
                                    <span>{{ chipsSummary(combo.locationLabels || [], 2) || '–' }}</span>
                                  </div>
                                </div>
                              </div>
                            </transition>
                          </div>
                        </div>
                      </div>
                    </section>

                    <!-- =================== TAB: DIENSTLEISTUNGEN =================== -->
                    <section
                      v-else-if="getDetailTab(item.id) === 'services'"
                      key="tab-services"
                    >
                      <div class="bookando-table-detail">
                        <div v-if="serviceGroupsFor(item).length">
                          <div
                            v-for="grp in serviceGroupsFor(item)"
                            :key="grp._key"
                            class="detail-row"
                          >
                            <div class="detail-label">
                              {{ grp.name }}:
                            </div>
                            <div class="detail-value bookando-text-sm">
                              <span>{{ chipsSummary(grp.services.map(s => s.name), 6) || '–' }}</span>
                            </div>
                          </div>
                        </div>
                        <div
                          v-else
                          class="bookando-text-sm bookando-text-muted"
                        >
                          –
                        </div>
                      </div>
                    </section>

                    <!-- ===================== TAB: KALENDER ===================== -->
                    <section
                      v-else-if="getDetailTab(item.id) === 'calendar'"
                      key="tab-calendar"
                    >
                      <div class="bookando-table-detail">
                        <template v-if="asArray(item.calendars).length">
                          <div
                            v-for="cal in asArray(item.calendars)"
                            :key="cal.id || cal.calendar_id || JSON.stringify(cal)"
                            class="detail-row"
                          >
                            <div class="detail-label">
                              {{ calendarLabel(cal) }}:
                            </div>
                            <div class="detail-value bookando-text-sm">
                              <span class="bookando-text-muted">{{ calendarSub(cal) }}</span>
                              <span
                                v-if="cal.mode"
                                class="bookando-ml-xs"
                              >• {{ cal.mode.toUpperCase() }}</span>
                            </div>
                          </div>
                        </template>
                        <div
                          v-else
                          class="bookando-text-sm bookando-text-muted"
                        >
                          –
                        </div>
                      </div>
                    </section>

                    <!-- ==================== TAB: AKTIVITÄT ==================== -->
                    <section
                      v-else
                      key="tab-activity"
                    >
                      <div class="bookando-table-detail">
                        <div class="detail-row">
                          <span class="detail-label">{{ t('mod.employees.stats.total_appointments') || 'Termine (gesamt)' }}:</span>
                          <span class="detail-value bookando-text-muted">{{ item.total_appointments ?? '–' }}</span>
                        </div>
                        <div class="detail-row">
                          <span class="detail-label">{{ t('mod.employees.stats.last_activity') || 'Letzte Aktivität' }}:</span>
                          <span class="detail-value bookando-text-muted">{{ item.last_activity ?? '–' }}</span>
                        </div>
                      </div>
                    </section>
                  </transition>
                </div>
              </div>
            </div>
          </div>
        </transition>
      </div>
    </div>

    <!-- Empty -->
    <div
      v-if="!items.length"
      class="bookando-table-cards-empty"
    >
      {{ t('ui.table.no_results') }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, reactive } from 'vue'
import { useI18n } from 'vue-i18n'
import AppCheckbox from '@core/Design/components/AppCheckbox.vue'
import AppButton from '@core/Design/components/AppButton.vue'
import AppPopover from '@core/Design/components/AppPopover.vue'
import AppIcon from '@core/Design/components/AppIcon.vue'
import AppAvatar from '@core/Design/components/AppAvatar.vue'
import AppTabs from '@core/Design/components/AppTabs.vue'
import {
  languageFlag, languageLabel, statusLabel, formatDatetime, formatDate,
  countryFlag, countryLabel, genderLabel
} from '@core/Util/formatters'
import { fieldLabel } from '@core/Util/i18n-helpers'
import { getEmployee } from '../api/EmployeesApi'

/* → ZENTRAL: Composable Cache + Display-Builder */
import {
  useEmployeeDetailsCache,
  buildWorkingDaysDisplay,
  buildSpecialDaysDisplay,
  type EmployeeVM,
} from '../../../composables/useEmployeeData'

const MODULE = 'employees'
const { t, locale } = useI18n()

/* Props & Emits */
const props = defineProps({
  items: { type: Array, required: true },
  selectedItems: { type: Array, default: () => [] },
  containerClass: { type: [String, Array, Object], default: '' }
})
const emit = defineEmits(['edit','delete','select','quick'])

/* Zentraler Details-Cache */
const { ensure, getVM } = useEmployeeDetailsCache((id) => getEmployee(Number(id)))

/* Expand (Card) */
const expandedItems = ref(new Set<number>())
async function toggleExpand(id: number) {
  const wasOpen = expandedItems.value.has(id)
  if (wasOpen) {
    expandedItems.value.delete(id)
  } else {
    await ensure(id)  // Details laden (einmalig gecached)
    expandedItems.value.add(id)
  }
}

/* Selection */
const allSelected = computed(() =>
  (props.items as any[]).length > 0 &&
  (props.items as any[]).every(i => (props.selectedItems as any[]).includes(i.id))
)
function toggleAll(val: boolean) {
  const ids = val ? (props.items as any[]).map(i => i.id) : []
  emit('select', ids)
}
function toggleItem(id: number, checked: boolean) {
  const arr = (props.selectedItems as any[]).slice()
  const idx = arr.indexOf(id)
  if (checked && idx === -1) arr.push(id)
  else if (!checked && idx !== -1) arr.splice(idx, 1)
  emit('select', arr)
}

/* Tabs – identisch zur bisherigen Logik */
type DetailTab = 'info' | 'workinghours' | 'days_off' | 'special_days' | 'services' | 'calendar' | 'activity'
const tabsDef = computed(() => ([
  { label: t('core.common.info') || t('mod.employees.tabs.info') || 'Info', value: 'info' },
  { label: t('mod.employees.form.working_days.title') || t('mod.employees.tabs.working_hours') || 'Arbeitszeiten', value: 'workinghours' },
  { label: t('mod.employees.form.days_off.title') || t('mod.employees.tabs.days_off') || 'Freie Tage', value: 'days_off' },
  { label: t('mod.employees.form.special_days.title') || t('mod.employees.tabs.special_days') || 'Besondere Tage', value: 'special_days' },
  { label: t('mod.employees.form.assigned_services.title') || t('mod.employees.tabs.assigned_services') || 'Dienstleistungen', value: 'services' },
  { label: t('core.common.calendar') || t('mod.employees.tabs.calendar') || 'Kalender', value: 'calendar' },
  { label: t('mod.employees.tabs.activity') || 'Aktivität', value: 'activity' }
]))
const detailTabs = ref<Map<number, DetailTab>>(new Map())
const lastTabs   = ref<Map<number, DetailTab>>(new Map())
function getDetailTab(id: number): DetailTab { return detailTabs.value.get(id) || 'info' }
function setDetailTab(id: number, value: DetailTab) { lastTabs.value.set(id, getDetailTab(id)); detailTabs.value.set(id, value) }
function panelTransition(id:number){
  const prev = lastTabs.value.get(id) || 'info'
  const next = getDetailTab(id)
  return prev === 'info' && next !== 'info' ? 'panel-slide-left'
       : prev !== 'info' && next === 'info' ? 'panel-slide-right'
       : 'panel-fade'
}

/* Height-Anim Helpers (bestehend) */
function getAnimWrapper(el: Element | null): HTMLElement | null { return (el?.closest('.details-anim') as HTMLElement) || null }
function getDetailsCard(el: Element | null): HTMLElement | null { return (el?.closest('.details-card') as HTMLElement) || null }
function onPanelBeforeLeave(el: Element) {
  const w = getAnimWrapper(el); if (!w) return
  const h = w.offsetHeight; w.style.height = `${h}px`; w.setAttribute('data-prev-h', String(h))
}
function onPanelEnter(el: Element) {
  const w = getAnimWrapper(el); const card = getDetailsCard(el); if (!w || !card) return
  const prev = Number(w.getAttribute('data-prev-h') || w.offsetHeight)
  if (!w.style.height) w.style.height = `${prev}px`
  const nextH = (el as HTMLElement).offsetHeight
  card.classList.remove('card-bounce-grow','card-bounce-shrink'); void (card as HTMLElement).offsetWidth
  card.classList.add(nextH >= prev ? 'card-bounce-grow' : 'card-bounce-shrink')
  requestAnimationFrame(() => { w.style.height = `${nextH}px` })
  const done = (evt: TransitionEvent) => { if (evt.propertyName !== 'height') return; w.removeEventListener('transitionend', done); w.style.height = ''; w.removeAttribute('data-prev-h') }
  w.addEventListener('transitionend', done)
}
function onPanelAfterEnter(_el: Element) {}

/* ===== Quick-Options ===== */
type QuickKey = 'soft_delete' | 'hard_delete' | 'block' | 'activate' | 'export'
type QuickOption = { value: QuickKey; label: string; icon: string; className?: string; disabled?: boolean; separatorAfter?: boolean }
const quickOptions = computed<QuickOption[]>(() => [
  { value: 'soft_delete', label: t('core.actions.soft_delete.label'), icon: 'user-minus',  className: 'bookando-text-danger' },
  { value: 'hard_delete', label: t('core.actions.hard_delete.label'), icon: 'trash-2',     className: 'bookando-text-danger' },
  { value: 'block',       label: t('core.actions.block.label'),       icon: 'user-x',      className: 'bookando-text-warning' },
  { value: 'activate',    label: t('core.actions.activate.label'),    icon: 'user-check',  className: 'bookando-text-success' },
  { value: 'export',      label: t('core.actions.export.label'),      icon: 'download',    className: 'bookando-text-accent' },
])
function onQuickOption(action: QuickKey, item: any, close?: () => void) { close?.(); (emit as any)('quick', { action, item }) }

/* ===== Helpers (Labels & Interval-Merge) ===== */
function initials(item: any) { return ((item.first_name?.[0] || '') + (item.last_name?.[0] || '')).toUpperCase() }
function statusClass(val: string) { return val === 'active' ? 'active' : val === 'blocked' ? 'inactive' : 'deleted' }
function normalizePhone(phone: string | number) { return String(phone).replace(/\s+/g, '') }
function avatarSrc(item: any): string {
  const v = item?.avatar_url
  if (!v) return ''
  if (typeof v === 'string') return v.trim() || ''
  if (typeof v === 'object') {
    if (typeof v.url === 'string' && v.url.trim()) return v.url
    if (typeof v.src === 'string' && v.src.trim()) return v.src
    if (v.sizes) {
      if (typeof v.sizes.thumbnail === 'string' && v.sizes.thumbnail.trim()) return v.sizes.thumbnail
      if (typeof v.sizes.medium === 'string' && v.sizes.medium.trim()) return v.sizes.medium
      if (typeof v.sizes.full === 'string' && v.sizes.full.trim()) return v.sizes.full
    }
  }
  return ''
}
function onAvatarError(_: Event, item: any) { try { item.avatar_url = '' } catch {} }
function asArray<T=any>(val: any): T[] { return Array.isArray(val) ? (val as T[]) : (val ? [val as T] : []) }
function chipsSummary(list: (string|number)[], max = 2) {
  const arr = (list || []).map(entry => String(entry)).filter(Boolean)
  if (!arr.length) return ''
  if (arr.length <= max) return arr.join(', ')
  return `${arr.slice(0, max).join(', ')} +${arr.length - max}`
}
function timeToMin(t: string) { const [h,m] = String(t||'').split(':').map(n=>Number(n)); return (isFinite(h)&&isFinite(m)) ? h*60+m : NaN }
function minToTime(n: number) { const h = Math.floor(n/60), m = n%60; return `${String(h).padStart(2,'0')}:${String(m).padStart(2,'0')}` }
function mergeIntervals(ints: {start:string,end:string}[]) {
  const list = (ints || []).map(i => ({ s: timeToMin(i.start), e: timeToMin(i.end) })).filter(x => isFinite(x.s) && isFinite(x.e) && x.s < x.e).sort((a,b)=>a.s-b.s)
  if (!list.length) return []
  const out: {s:number,e:number}[] = [ { ...list[0] } ]
  for (let i=1;i<list.length;i++){ const cur=list[i], last=out[out.length-1]; if (cur.s <= last.e) { last.e = Math.max(last.e, cur.e) } else { out.push({ ...cur }) } }
  return out.map(x => ({ start: minToTime(x.s), end: minToTime(x.e) }))
}
function uniq<T>(arr:T[]) { return Array.from(new Set(arr)) }

/* ===== Anzeige-Builder auf Basis des VM aus dem Cache ===== */
function svcNames(vm: EmployeeVM, ids:number[]) {
  const map = new Map(vm.assignedServicesFlat.map(s => [s.id, s.name]))
  return ids.map(id => map.get(id) || String(id))
}
function locNames(vm: EmployeeVM, ids:number[]) {
  const pairs = asArray(vm.form.work_locations).map((l:any)=>[Number(l?.id ?? l?.value ?? l), String(l?.label ?? l?.name ?? l)])
  const map = new Map(pairs)
  return ids.map(id => map.get(id) || String(id))
}

/* ===== Arbeitszeiten ===== */
type SD = { start:string; end:string }
type Combo = { serviceIds:number[]; serviceLabels?:string[]; locationIds:number[]; locationLabels?:string[]; work:SD[]; breaks:SD[] }
type WorkDay = { key:string; label:string; combos:Combo[]; summary:{ blocks:SD[]; blockCount:number; serviceCount:number; locationCount:number } }

const week = [
  { key: 'mon', label: t('ui.weekdays.mon_short') || 'Mo' },
  { key: 'tue', label: t('ui.weekdays.tue_short') || 'Di' },
  { key: 'wed', label: t('ui.weekdays.wed_short') || 'Mi' },
  { key: 'thu', label: t('ui.weekdays.thu_short') || 'Do' },
  { key: 'fri', label: t('ui.weekdays.fri_short') || 'Fr' },
  { key: 'sat', label: t('ui.weekdays.sat_short') || 'Sa' },
  { key: 'sun', label: t('ui.weekdays.sun_short') || 'So' },
]

function workingDaysFor(item:any): WorkDay[] {
  const vm = getVM(Number(item.id))
  if (!vm) return week.map(d => ({ key:d.key, label:d.label, combos:[], summary:{blocks:[],blockCount:0,serviceCount:0,locationCount:0} }))
  const display = buildWorkingDaysDisplay(vm)
  const out: WorkDay[] = display.map(d => {
    const combos: Combo[] = d.combos.map(c => ({
      serviceIds: [...(c.serviceIds||[])],
      locationIds: [...(c.locationIds||[])],
      work: [...(c.work||[])],
      breaks: [...(c.breaks||[])],
      serviceLabels: svcNames(vm, c.serviceIds||[]),
      locationLabels: locNames(vm, c.locationIds||[]),
    }))
    const merged = mergeIntervals(combos.flatMap(c => c.work))
    const svcCount = uniq(combos.flatMap(c=>c.serviceIds)).length
    const locCount = uniq(combos.flatMap(c=>c.locationIds)).length
    return { key: d.key, label: d.label, combos, summary: { blocks: merged, blockCount: merged.length, serviceCount: svcCount, locationCount: locCount } }
  })
  return out
}

/* Workday-Details Toggle */
const openWorkdays = reactive<Record<number, Set<number>>>({})
function isWorkdayOpen(itemId:number, dayIndex:number){ return openWorkdays[itemId]?.has(dayIndex) }
function toggleWorkdayDetails(itemId:number, dayIndex:number){
  if (!openWorkdays[itemId]) openWorkdays[itemId] = new Set()
  const s = openWorkdays[itemId]; s.has(dayIndex) ? s.delete(dayIndex) : s.add(dayIndex)
}

/* ===== Freie Tage ===== */
type DayOff = { id?:number; title?:string; note?:string; dateStart:string; dateEnd:string; repeatYearly?:boolean; _key:string }
function todayYMD(){ const d=new Date(); const y=d.getFullYear(), m=String(d.getMonth()+1).padStart(2,'0'), dd=String(d.getDate()).padStart(2,'0'); return `${y}-${m}-${dd}` }
function groupedDaysOff(item:any){
  const vm = getVM(Number(item.id))
  const list = (vm?.daysOff || []).map((r:any, i:number) => {
    const ds = String(r.dateStart || '').slice(0,10)
    const de = String(r.dateEnd   || r.dateStart || '').slice(0,10)
    return { id: Number(r.id ?? i), title: r.title || '', note: r.note || '', dateStart: ds, dateEnd: de||ds, repeatYearly: !!r.repeatYearly, _key: `${ds}_${de}_${r.id ?? i}` } as DayOff
  }).filter(e => e.dateStart)
  const today = todayYMD()
  const upcoming = list.filter(e => (e.dateEnd >= today)).sort((a,b)=>a.dateStart.localeCompare(b.dateStart))
  const past     = list.filter(e => (e.dateEnd <  today)).sort((a,b)=>b.dateStart.localeCompare(a.dateStart))
  return { upcoming, past }
}
const showMoreUp = reactive<Record<number, boolean>>({})
const showMorePa = reactive<Record<number, boolean>>({})
function isShowingMore(itemId:number, kind:'up'|'pa'){ return (kind==='up' ? showMoreUp[itemId] : showMorePa[itemId]) || false }
function toggleShowMore(itemId:number, kind:'up'|'pa'){ if (kind==='up') showMoreUp[itemId] = !showMoreUp[itemId]; else showMorePa[itemId] = !showMorePa[itemId] }
function showMoreNeeded(item:any, kind:'up'|'pa'){ const g=groupedDaysOff(item); return (kind==='up' ? g.upcoming.length : g.past.length) > 5 }
function visibleUpcoming(item:any){ const g=groupedDaysOff(item); return isShowingMore(item.id,'up') ? g.upcoming : g.upcoming.slice(0,5) }
function visiblePast(item:any){ const g=groupedDaysOff(item); return isShowingMore(item.id,'pa') ? g.past : g.past.slice(0,5) }
const openDayOff = reactive<Record<number, Set<string>>>({})
function isDayOffOpen(itemId:number, key:string){ return openDayOff[itemId]?.has(key) }
function toggleDayOffDetails(itemId:number, key:string){
  if (!openDayOff[itemId]) openDayOff[itemId] = new Set()
  const s = openDayOff[itemId]; s.has(key) ? s.delete(key) : s.add(key)
}
function formatRange(a:string,b?:string){ const A=formatDate(a, locale.value); const B=formatDate(b||a, locale.value); return (A===B)?A:`${A} – ${B}` }

/* ===== Besondere Tage (Cards → Sets) ===== */
type SpecialCombo = { serviceIds:number[]; serviceLabels?:string[]; locationIds:number[]; locationLabels?:string[]; work:SD[]; breaks:SD[] }
type SpecialSet = { start:string; end?:string|null; combos:SpecialCombo[]; summary:{blocks:SD[]; blockCount:number; serviceCount:number; locationCount:number}; _key:string }

function specialSetsFor(item:any): SpecialSet[] {
  const vm = getVM(Number(item.id))
  if (!vm) return []
  const cards = buildSpecialDaysDisplay(vm)
  const out: SpecialSet[] = []
  for (const c of cards) {
    const combos: SpecialCombo[] = (c.items||[]).map(it => ({
      serviceIds: [...(it.serviceIds||[])],
      locationIds: [...(it.locationIds||[])],
      work: [...(it.work||[])],
      breaks: [...(it.breaks||[])],
      serviceLabels: svcNames(vm, it.serviceIds||[]),
      locationLabels: locNames(vm, it.locationIds||[]),
    }))
    const blocks = mergeIntervals(combos.flatMap(k=>k.work))
    out.push({
      start: String(c.dateStart||'').slice(0,10),
      end: String(c.dateEnd||c.dateStart||'').slice(0,10) || null,
      combos,
      summary: { blocks, blockCount: blocks.length, serviceCount: uniq(combos.flatMap(x=>x.serviceIds)).length, locationCount: uniq(combos.flatMap(x=>x.locationIds)).length },
      _key: `${c.dateStart||''}_${c.dateEnd||c.dateStart||''}`
    })
  }
  out.sort((a,b)=>a.start.localeCompare(b.start))
  return out
}

const openSpecial = reactive<Record<number, Set<string>>>({})
function isSpecialSetOpen(itemId:number, key:string){ return openSpecial[itemId]?.has(key) }
function toggleSpecialSet(itemId:number, key:string){
  if (!openSpecial[itemId]) openSpecial[itemId] = new Set()
  const s = openSpecial[itemId]; s.has(key) ? s.delete(key) : s.add(key)
}

/* ===== Services (Gruppen-Chips) aus VM ===== */
function serviceGroupsFor(item:any){
  const vm = getVM(Number(item.id))
  const groups = (vm?.serviceGroups || []).map(g => ({
    _key: String(g.id ?? g.name),
    name: String(g.name || (t('core.common.general') || 'Allgemein')),
    services: (g.services || []).map(s => ({ id: s.id, name: s.name }))
  }))
  groups.sort((a,b)=>a.name.localeCompare(b.name,'de'))
  for (const g of groups) g.services.sort((a:any,b:any)=>a.name.localeCompare(b.name,'de'))
  return groups
}

/* ===== Kalender (readonly) – unverändert ===== */
function calendarLabel(cal:any){
  switch (cal?.calendar) {
    case 'google':   return t('mod.employees.form.calendar.labels.google_calendar')   || 'Google-Kalender'
    case 'outlook':  return t('mod.employees.form.calendar.labels.outlook_calendar')  || 'Outlook-Kalender'
    case 'exchange': return t('mod.employees.form.calendar.labels.exchange_calendar') || 'Exchange-Kalender'
    case 'apple':    return t('mod.employees.form.calendar.labels.apple_calendar')    || 'Apple / iCloud'
    default: return cal?.label || (t('core.common.calendar') || 'Kalender')
  }
}
function calendarSub(cal:any){
  if (cal?.subLabel) return cal.subLabel
  switch (cal?.calendar) {
    case 'google':   return 'Gmail, G Suite'
    case 'outlook':  return 'Office-365/Outlook/Hotmail'
    case 'exchange': return 'EWS/AutoDiscover'
    case 'apple':    return 'iCloud / ICS'
    default:         return ''
  }
}
</script>
