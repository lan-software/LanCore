<script setup lang="ts">
import Image from '@tiptap/extension-image'
import Link from '@tiptap/extension-link'
import Placeholder from '@tiptap/extension-placeholder'
import StarterKit from '@tiptap/starter-kit'
import { useEditor, EditorContent } from '@tiptap/vue-3'
import { Bold, Italic, Strikethrough, Code, List, ListOrdered, Quote, Undo, Redo, Link as LinkIcon, Image as ImageIcon, Heading1, Heading2, Heading3, RemoveFormatting, Minus } from 'lucide-vue-next'
import { watch } from 'vue'
import { Button } from '@/components/ui/button'

const props = defineProps<{
    modelValue: string
    placeholder?: string
}>()

const emit = defineEmits<{
    'update:modelValue': [value: string]
}>()

const editor = useEditor({
    content: props.modelValue,
    extensions: [
        StarterKit,
        Image.configure({ inline: false }),
        Link.configure({ openOnClick: false }),
        Placeholder.configure({ placeholder: props.placeholder ?? 'Write your content here…' }),
    ],
    onUpdate: ({ editor }) => {
        emit('update:modelValue', editor.getHTML())
    },
})

watch(() => props.modelValue, (value) => {
    if (editor.value && editor.value.getHTML() !== value) {
        editor.value.commands.setContent(value, false)
    }
})

function setLink() {
    const url = window.prompt('Enter URL:')

    if (url) {
        editor.value?.chain().focus().setLink({ href: url }).run()
    }
}

function addImage() {
    const url = window.prompt('Enter image URL:')

    if (url) {
        editor.value?.chain().focus().setImage({ src: url }).run()
    }
}
</script>

<template>
    <div class="rounded-md border">
        <div v-if="editor" class="flex flex-wrap gap-0.5 border-b p-1">
            <Button type="button" variant="ghost" size="icon" class="size-8" :class="{ 'bg-muted': editor.isActive('heading', { level: 1 }) }" @click="editor.chain().focus().toggleHeading({ level: 1 }).run()">
                <Heading1 class="size-4" />
            </Button>
            <Button type="button" variant="ghost" size="icon" class="size-8" :class="{ 'bg-muted': editor.isActive('heading', { level: 2 }) }" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">
                <Heading2 class="size-4" />
            </Button>
            <Button type="button" variant="ghost" size="icon" class="size-8" :class="{ 'bg-muted': editor.isActive('heading', { level: 3 }) }" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()">
                <Heading3 class="size-4" />
            </Button>
            <div class="mx-1 w-px bg-border" />
            <Button type="button" variant="ghost" size="icon" class="size-8" :class="{ 'bg-muted': editor.isActive('bold') }" @click="editor.chain().focus().toggleBold().run()">
                <Bold class="size-4" />
            </Button>
            <Button type="button" variant="ghost" size="icon" class="size-8" :class="{ 'bg-muted': editor.isActive('italic') }" @click="editor.chain().focus().toggleItalic().run()">
                <Italic class="size-4" />
            </Button>
            <Button type="button" variant="ghost" size="icon" class="size-8" :class="{ 'bg-muted': editor.isActive('strike') }" @click="editor.chain().focus().toggleStrike().run()">
                <Strikethrough class="size-4" />
            </Button>
            <Button type="button" variant="ghost" size="icon" class="size-8" :class="{ 'bg-muted': editor.isActive('code') }" @click="editor.chain().focus().toggleCode().run()">
                <Code class="size-4" />
            </Button>
            <div class="mx-1 w-px bg-border" />
            <Button type="button" variant="ghost" size="icon" class="size-8" :class="{ 'bg-muted': editor.isActive('bulletList') }" @click="editor.chain().focus().toggleBulletList().run()">
                <List class="size-4" />
            </Button>
            <Button type="button" variant="ghost" size="icon" class="size-8" :class="{ 'bg-muted': editor.isActive('orderedList') }" @click="editor.chain().focus().toggleOrderedList().run()">
                <ListOrdered class="size-4" />
            </Button>
            <Button type="button" variant="ghost" size="icon" class="size-8" :class="{ 'bg-muted': editor.isActive('blockquote') }" @click="editor.chain().focus().toggleBlockquote().run()">
                <Quote class="size-4" />
            </Button>
            <Button type="button" variant="ghost" size="icon" class="size-8" @click="editor.chain().focus().setHorizontalRule().run()">
                <Minus class="size-4" />
            </Button>
            <div class="mx-1 w-px bg-border" />
            <Button type="button" variant="ghost" size="icon" class="size-8" :class="{ 'bg-muted': editor.isActive('link') }" @click="setLink">
                <LinkIcon class="size-4" />
            </Button>
            <Button type="button" variant="ghost" size="icon" class="size-8" @click="addImage">
                <ImageIcon class="size-4" />
            </Button>
            <div class="mx-1 w-px bg-border" />
            <Button type="button" variant="ghost" size="icon" class="size-8" @click="editor.chain().focus().clearNodes().unsetAllMarks().run()">
                <RemoveFormatting class="size-4" />
            </Button>
            <Button type="button" variant="ghost" size="icon" class="size-8" :disabled="!editor.can().undo()" @click="editor.chain().focus().undo().run()">
                <Undo class="size-4" />
            </Button>
            <Button type="button" variant="ghost" size="icon" class="size-8" :disabled="!editor.can().redo()" @click="editor.chain().focus().redo().run()">
                <Redo class="size-4" />
            </Button>
        </div>
        <EditorContent :editor="editor" class="prose prose-sm dark:prose-invert max-w-none p-4 min-h-[200px] focus-within:outline-none [&_.tiptap]:outline-none [&_.tiptap_p.is-editor-empty:first-child::before]:text-muted-foreground [&_.tiptap_p.is-editor-empty:first-child::before]:content-[attr(data-placeholder)] [&_.tiptap_p.is-editor-empty:first-child::before]:float-left [&_.tiptap_p.is-editor-empty:first-child::before]:h-0 [&_.tiptap_p.is-editor-empty:first-child::before]:pointer-events-none" />
    </div>
</template>
