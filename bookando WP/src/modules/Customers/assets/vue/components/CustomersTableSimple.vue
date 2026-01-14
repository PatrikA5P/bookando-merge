<!-- CustomersTableSimple.vue - SUPER SIMPLE TEST TABLE -->
<template>
  <div style="border: 3px solid green; padding: 2rem; background: white;">
    <h2 style="color: green;">
      ✅ SIMPLE TABLE WORKS!
    </h2>

    <table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
      <thead>
        <tr style="background: #f0f0f0;">
          <th style="border: 1px solid #ccc; padding: 0.5rem;">
            ID
          </th>
          <th style="border: 1px solid #ccc; padding: 0.5rem;">
            Name
          </th>
          <th style="border: 1px solid #ccc; padding: 0.5rem;">
            Email
          </th>
          <th style="border: 1px solid #ccc; padding: 0.5rem;">
            Status
          </th>
        </tr>
      </thead>
      <tbody>
        <tr
          v-for="item in items"
          :key="item.id"
          style="border-bottom: 1px solid #eee;"
        >
          <td style="border: 1px solid #ccc; padding: 0.5rem;">
            {{ item.id }}
          </td>
          <td style="border: 1px solid #ccc; padding: 0.5rem;">
            {{ item.first_name }} {{ item.last_name }}
          </td>
          <td style="border: 1px solid #ccc; padding: 0.5rem;">
            {{ item.email }}
          </td>
          <td style="border: 1px solid #ccc; padding: 0.5rem;">
            <span
              :style="{
                padding: '0.25rem 0.5rem',
                borderRadius: '4px',
                background: item.status === 'active' ? '#d4edda' : '#f8d7da',
                color: item.status === 'active' ? '#155724' : '#721c24'
              }"
            >
              {{ item.status }}
            </span>
          </td>
        </tr>
      </tbody>
    </table>

    <div style="margin-top: 1rem; color: #666;">
      Showing {{ items.length }} customers
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  items: any[]
}>()
</script>
