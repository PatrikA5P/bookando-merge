<!-- AppRichTextField.vue -->

<template>
  <div class="bookando-field bookando-field--richtext">
    <label
      v-if="label"
      class="bookando-label"
      :for="id"
    >
      {{ label }}
      <span
        v-if="required"
        aria-hidden="true"
      >*</span>
    </label>

    <div
      class="bookando-richtext-wrapper"
      :style="wrapperStyle"
    >
      <!-- Toolbar -->
      <div
        v-if="editor"
        class="tiptap-toolbar"
      >
        <!-- Text Formatting -->
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('bold') }"
          @click="editor.chain().focus().toggleBold().run()"
          title="Bold"
        >
          <strong>B</strong>
        </button>
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('italic') }"
          @click="editor.chain().focus().toggleItalic().run()"
          title="Italic"
        >
          <em>I</em>
        </button>
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('underline') }"
          @click="editor.chain().focus().toggleUnderline().run()"
          title="Underline"
        >
          <u>U</u>
        </button>
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('strike') }"
          @click="editor.chain().focus().toggleStrike().run()"
          title="Strike"
        >
          <s>S</s>
        </button>

        <span class="separator" />

        <!-- Block Formatting -->
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('blockquote') }"
          @click="editor.chain().focus().toggleBlockquote().run()"
          title="Blockquote"
        >
          "
        </button>
        <button
          type="button"
          @click="setLink"
          :class="{ 'is-active': editor.isActive('link') }"
          title="Link"
        >
          🔗
        </button>

        <span class="separator" />

        <!-- Lists -->
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('orderedList') }"
          @click="editor.chain().focus().toggleOrderedList().run()"
          title="Ordered List"
        >
          1.
        </button>
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('bulletList') }"
          @click="editor.chain().focus().toggleBulletList().run()"
          title="Bullet List"
        >
          •
        </button>

        <span class="separator" />

        <!-- Headings -->
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('heading', { level: 1 }) }"
          @click="editor.chain().focus().toggleHeading({ level: 1 }).run()"
          title="Heading 1"
        >
          H1
        </button>
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('heading', { level: 2 }) }"
          @click="editor.chain().focus().toggleHeading({ level: 2 }).run()"
          title="Heading 2"
        >
          H2
        </button>
        <button
          type="button"
          :class="{ 'is-active': editor.isActive('heading', { level: 3 }) }"
          @click="editor.chain().focus().toggleHeading({ level: 3 }).run()"
          title="Heading 3"
        >
          H3
        </button>

        <span class="separator" />

        <!-- Undo/Redo -->
        <button
          type="button"
          @click="editor.chain().focus().undo().run()"
          :disabled="!editor.can().undo()"
          title="Undo"
        >
          ↶
        </button>
        <button
          type="button"
          @click="editor.chain().focus().redo().run()"
          :disabled="!editor.can().redo()"
          title="Redo"
        >
          ↷
        </button>
      </div>

      <!-- Editor Content -->
      <EditorContent
        :id="id"
        :editor="editor"
        class="bookando-tiptap"
      />
    </div>

    <div
      v-if="hint"
      class="bookando-field-hint"
    >
      {{ hint }}
    </div>
    <div
      v-if="error"
      class="bookando-field-error"
    >
      {{ error }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, watch, onBeforeUnmount } from 'vue'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import TextAlign from '@tiptap/extension-text-align'
import Color from '@tiptap/extension-color'
import { TextStyle } from '@tiptap/extension-text-style' // benannter Export in v3 

const props = defineProps<{
  modelValue: string
  id?: string
  label?: string
  placeholder?: string
  hint?: string
  error?: string
  required?: boolean
  minHeight?: number | string
  maxHeight?: number | string
}>()

const emit = defineEmits<{ (event: 'update:modelValue', value: string): void }>()

// Tiptap Editor
const editor = useEditor({
  content: props.modelValue || '',
  extensions: [
    StarterKit.configure({
      // Undo/Redo ist im StarterKit schon drin
      // (Standard-Tiefe 100, reicht in der Regel aus) 
      link: {
        openOnClick: false,
        HTMLAttributes: {
          class: 'tiptap-link',
        },
      },
    }),
    TextStyle,
    Color,
    TextAlign.configure({
      types: ['heading', 'paragraph'],
    }),
  ],
  editorProps: {
    attributes: {
      class: 'tiptap-editor-content',
    },
  },
  onUpdate: ({ editor }) => {
    const html = editor.getHTML()
    emit('update:modelValue', html)
  },
})

// externe Aenderungen am v-model ins Editor-Content spiegeln
watch(
  () => props.modelValue,
  (value) => {
    if (!editor.value) return
    const current = editor.value.getHTML()
    if (value !== undefined && value !== current) {
      editor.value.commands.setContent(value, false)
    }
  },
)

// Link setzen / entfernen
const setLink = () => {
  if (!editor.value) return

  const previousUrl = editor.value.getAttributes('link').href
  const url = window.prompt('URL', previousUrl)

  // abgebrochen
  if (url === null) {
    return
  }

  // leer => Link entfernen
  if (url === '') {
    editor.value
      .chain()
      .focus()
      .extendMarkRange('link')
      .unsetLink()
      .run()
    return
  }

  // Link setzen / updaten
  editor.value
    .chain()
    .focus()
    .extendMarkRange('link')
    .setLink({ href: url })
    .run()
}

// Cleanup
onBeforeUnmount(() => {
  editor.value?.destroy()
})

function toCssUnit(v?: number | string) {
  if (v === undefined || v === null || v === '') return undefined
  return typeof v === 'number' ? `${v}px` : v
}

const wrapperStyle = computed(() => ({
  '--rt-min-h': toCssUnit(props.minHeight) || '160px',
  '--rt-max-h': toCssUnit(props.maxHeight) || 'auto',
}))
</script>

<style scoped>
.bookando-field--richtext .bookando-richtext-wrapper {
  border: 1px solid var(--bookando-border, #e6e8ea);
  border-radius: 8px;
  background: #fff;
  overflow: hidden;
}

/* Toolbar styling */
.tiptap-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 4px;
  padding: 8px;
  border-bottom: 1px solid var(--bookando-border, #e6e8ea);
  background: var(--bookando-bg, #fff);
}

.tiptap-toolbar button {
  padding: 6px 10px;
  border: 1px solid transparent;
  border-radius: 4px;
  background: transparent;
  color: var(--bookando-text, #354052);
  cursor: pointer;
  font-size: 14px;
  line-height: 1;
  transition: all 0.2s;
}

.tiptap-toolbar button:hover:not(:disabled) {
  background: rgba(82, 76, 255, 0.1);
  border-color: rgba(82, 76, 255, 0.2);
}

.tiptap-toolbar button.is-active {
  background: rgba(82, 76, 255, 0.15);
  border-color: rgba(82, 76, 255, 0.3);
  color: var(--bookando-accent, #524cff);
}

.tiptap-toolbar button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.tiptap-toolbar .separator {
  width: 1px;
  height: 24px;
  background: var(--bookando-border, #e6e8ea);
  margin: 0 4px;
}

/* Editor content styling */
.bookando-tiptap :deep(.tiptap-editor-content) {
  min-height: var(--rt-min-h);
  max-height: var(--rt-max-h);
  overflow-y: auto;
  padding: 12px 14px;
  font-size: 14px;
  line-height: 1.6;
  color: var(--bookando-text, #354052);
}

.bookando-tiptap :deep(.tiptap-editor-content:focus) {
  outline: none;
}

/* Tiptap content styling */
.bookando-tiptap :deep(.ProseMirror) {
  outline: none;
}

.bookando-tiptap :deep(.ProseMirror p) {
  margin: 0 0 0.75em 0;
}

.bookando-tiptap :deep(.ProseMirror p:last-child) {
  margin-bottom: 0;
}

.bookando-tiptap :deep(.ProseMirror h1),
.bookando-tiptap :deep(.ProseMirror h2),
.bookando-tiptap :deep(.ProseMirror h3),
.bookando-tiptap :deep(.ProseMirror h4),
.bookando-tiptap :deep(.ProseMirror h5),
.bookando-tiptap :deep(.ProseMirror h6) {
  margin: 1em 0 0.5em 0;
  line-height: 1.3;
  font-weight: 600;
}

.bookando-tiptap :deep(.ProseMirror h1) { font-size: 2em; }
.bookando-tiptap :deep(.ProseMirror h2) { font-size: 1.5em; }
.bookando-tiptap :deep(.ProseMirror h3) { font-size: 1.25em; }

.bookando-tiptap :deep(.ProseMirror ul),
.bookando-tiptap :deep(.ProseMirror ol) {
  padding-left: 1.5em;
  margin: 0.75em 0;
}

.bookando-tiptap :deep(.ProseMirror li) {
  margin: 0.25em 0;
}

.bookando-tiptap :deep(.ProseMirror blockquote) {
  border-left: 3px solid var(--bookando-border, #e6e8ea);
  padding-left: 1em;
  margin: 1em 0;
  color: #666;
}

.bookando-tiptap :deep(.ProseMirror a) {
  color: var(--bookando-accent, #524cff);
  text-decoration: underline;
}

.bookando-tiptap :deep(.ProseMirror code) {
  background: rgba(0, 0, 0, 0.05);
  padding: 0.2em 0.4em;
  border-radius: 3px;
  font-family: 'Courier New', monospace;
  font-size: 0.9em;
}

.bookando-tiptap :deep(.ProseMirror pre) {
  background: rgba(0, 0, 0, 0.05);
  padding: 1em;
  border-radius: 6px;
  overflow-x: auto;
  margin: 1em 0;
}

.bookando-tiptap :deep(.ProseMirror pre code) {
  background: none;
  padding: 0;
  font-size: 0.9em;
}

/* Placeholder (funktioniert, wenn du spaeter die Placeholder-Extension einbindest) */
.bookando-tiptap :deep(.ProseMirror p.is-editor-empty:first-child::before) {
  content: attr(data-placeholder);
  float: left;
  color: #adb5bd;
  pointer-events: none;
  height: 0;
}

/* Focus styling */
.bookando-field--richtext:focus-within .bookando-richtext-wrapper {
  outline: 2px solid rgba(82, 76, 255, 0.25);
  outline-offset: 2px;
}
</style>
