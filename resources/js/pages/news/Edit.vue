<script setup lang="ts">
import { update, destroy } from '@/actions/App/Domain/News/Http/Controllers/NewsArticleController'
import { update as updateComment, destroy as destroyComment, approve } from '@/actions/App/Domain/News/Http/Controllers/NewsCommentController'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import RichTextEditor from '@/components/RichTextEditor.vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Checkbox } from '@/components/ui/checkbox'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Textarea } from '@/components/ui/textarea'
import AppLayout from '@/layouts/AppLayout.vue'
import { index as newsRoute } from '@/routes/news'
import { show as newsShow } from '@/routes/news'
import type { BreadcrumbItem } from '@/types'
import type { NewsArticle, NewsComment } from '@/types/domain'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { Check, ExternalLink, Trash2, X } from 'lucide-vue-next'
import { ref, watch } from 'vue'

const props = defineProps<{
    article: NewsArticle
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: newsRoute().url },
    { title: 'News', href: newsRoute().url },
    { title: 'Edit', href: '#' },
]

const form = useForm({
    title: props.article.title,
    summary: props.article.summary ?? '',
    content: props.article.content ?? '',
    tags: props.article.tags ?? [],
    image: null as File | null,
    remove_image: false,
    visibility: props.article.visibility,
    is_archived: props.article.is_archived,
    comments_enabled: props.article.comments_enabled,
    comments_require_approval: props.article.comments_require_approval,
    notify_users: props.article.notify_users,
    publish_now: false,
    meta_title: props.article.meta_title ?? '',
    meta_description: props.article.meta_description ?? '',
    og_title: props.article.og_title ?? '',
    og_description: props.article.og_description ?? '',
    og_image: null as File | null,
    remove_og_image: false,
    published_at: props.article.published_at ? props.article.published_at.slice(0, 16) : '',
})

const tagInput = ref('')
const imagePreview = ref<string | null>(props.article.image_url ?? null)
const ogImagePreview = ref<string | null>(props.article.og_image_url ?? null)
const showDeleteDialog = ref(false)
const publishMode = ref<'none' | 'now' | 'schedule'>(props.article.published_at ? 'schedule' : 'none')

watch(publishMode, (mode) => {
    if (mode !== 'schedule') {
        form.published_at = ''
    }
})

function addTag() {
    const tag = tagInput.value.trim().toLowerCase()
    if (tag && !form.tags.includes(tag)) {
        form.tags.push(tag)
    }
    tagInput.value = ''
}

function removeTag(index: number) {
    form.tags.splice(index, 1)
}

function handleTagKeydown(e: KeyboardEvent) {
    if (e.key === 'Enter' || e.key === ',') {
        e.preventDefault()
        addTag()
    }
}

function handleImageChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (file) {
        form.image = file
        form.remove_image = false
        imagePreview.value = URL.createObjectURL(file)
    }
}

function removeImage() {
    form.image = null
    form.remove_image = true
    imagePreview.value = null
}

function handleOgImageChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0]
    if (file) {
        form.og_image = file
        form.remove_og_image = false
        ogImagePreview.value = URL.createObjectURL(file)
    }
}

function removeOgImage() {
    form.og_image = null
    form.remove_og_image = true
    ogImagePreview.value = null
}

function submit() {
    form.publish_now = publishMode.value === 'now'
    form.post(update({ newsArticle: props.article.id }).url, {
        preserveScroll: true,
    })
}

function deleteArticle() {
    router.delete(destroy({ newsArticle: props.article.id }).url)
}

function approveComment(comment: NewsComment) {
    router.post(approve({ newsComment: comment.id }).url, {}, { preserveScroll: true })
}

function deleteComment(comment: NewsComment) {
    router.delete(destroyComment({ newsComment: comment.id }).url, { preserveScroll: true })
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}
</script>

<template>
    <Head :title="`Edit: ${article.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-8 p-4 max-w-3xl">
            <div class="flex items-center justify-between">
                <Link :href="newsRoute().url" class="text-sm text-muted-foreground hover:text-foreground">
                    &larr; Back to News
                </Link>
                <a v-if="article.visibility === 'public' && article.published_at" :href="`/news/${article.slug}`" target="_blank" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground">
                    <ExternalLink class="size-3.5" />
                    View Article
                </a>
            </div>

            <form @submit.prevent="submit" class="space-y-8">
                <!-- Basic Information -->
                <div class="space-y-4">
                    <Heading variant="small" title="Article Information" description="Edit the article details" />

                    <div class="grid gap-2">
                        <Label for="title">Title</Label>
                        <Input id="title" v-model="form.title" required placeholder="Article title" />
                        <InputError :message="form.errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="summary">Summary</Label>
                        <Textarea id="summary" v-model="form.summary" rows="3" placeholder="Brief summary shown on the welcome page…" />
                        <p class="text-xs text-muted-foreground">Max 500 characters. Displayed as a preview on the homepage.</p>
                        <InputError :message="form.errors.summary" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Content</Label>
                        <RichTextEditor v-model="form.content" placeholder="Write the article content…" />
                        <InputError :message="form.errors.content" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="tags">Tags</Label>
                        <div class="flex flex-wrap gap-1.5 mb-1.5">
                            <span v-for="(tag, index) in form.tags" :key="tag" class="inline-flex items-center gap-1 rounded-full bg-secondary px-2.5 py-0.5 text-xs font-medium">
                                {{ tag }}
                                <button type="button" @click="removeTag(index)" class="text-muted-foreground hover:text-foreground">
                                    <X class="size-3" />
                                </button>
                            </span>
                        </div>
                        <Input id="tags" v-model="tagInput" placeholder="Type a tag and press Enter…" @keydown="handleTagKeydown" @blur="addTag" />
                        <InputError :message="form.errors.tags" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="image">Banner Image</Label>
                        <div v-if="imagePreview" class="relative">
                            <img :src="imagePreview" alt="Banner preview" class="max-h-48 rounded-md object-cover" />
                            <Button type="button" variant="destructive" size="icon" class="absolute top-2 right-2 size-6" @click="removeImage">
                                <X class="size-3" />
                            </Button>
                        </div>
                        <Input id="image" type="file" accept="image/*" @change="handleImageChange" />
                        <InputError :message="form.errors.image" />
                    </div>
                </div>

                <!-- Visibility & Settings -->
                <div class="space-y-4">
                    <Heading variant="small" title="Publishing" description="Control visibility and publishing settings" />

                    <div class="grid gap-2">
                        <Label for="visibility">Visibility</Label>
                        <Select v-model="form.visibility">
                            <SelectTrigger>
                                <SelectValue placeholder="Select visibility" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="draft">Draft</SelectItem>
                                <SelectItem value="internal">Internal</SelectItem>
                                <SelectItem value="public">Public</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.visibility" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Publishing Behavior</Label>
                        <Select v-model="publishMode">
                            <SelectTrigger>
                                <SelectValue placeholder="Select publishing behavior" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">No publish date</SelectItem>
                                <SelectItem value="now">Publish Now</SelectItem>
                                <SelectItem value="schedule">Schedule for later</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="publishMode === 'now'" class="text-xs text-muted-foreground">The article will be published immediately with the current date and time.</p>
                        <p v-if="publishMode === 'none'" class="text-xs text-muted-foreground">The article will not have a publish date. Set one later to publish.</p>
                    </div>

                    <div v-if="publishMode === 'schedule'" class="grid gap-2">
                        <Label for="published_at">Publish Date</Label>
                        <Input id="published_at" v-model="form.published_at" type="datetime-local" />
                        <p class="text-xs text-muted-foreground">The article will become visible at this date and time.</p>
                        <InputError :message="form.errors.published_at" />
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="is_archived" v-model:checked="form.is_archived" />
                        <Label for="is_archived" class="text-sm font-normal">Archived</Label>
                    </div>
                </div>

                <!-- Comment Settings -->
                <div class="space-y-4">
                    <Heading variant="small" title="Comments" description="Configure comment settings for this article" />

                    <div class="flex items-center gap-2">
                        <Checkbox id="comments_enabled" v-model:checked="form.comments_enabled" />
                        <Label for="comments_enabled" class="text-sm font-normal">Allow comments</Label>
                    </div>

                    <div class="flex items-center gap-2">
                        <Checkbox id="comments_require_approval" v-model:checked="form.comments_require_approval" />
                        <Label for="comments_require_approval" class="text-sm font-normal">Comments require admin approval</Label>
                    </div>
                </div>

                <!-- Notification Settings -->
                <div class="space-y-4">
                    <Heading variant="small" title="Notifications" description="Configure user notification settings" />

                    <div class="flex items-center gap-2">
                        <Checkbox id="notify_users" v-model:checked="form.notify_users" />
                        <Label for="notify_users" class="text-sm font-normal">Notify users when published</Label>
                    </div>
                </div>

                <!-- SEO Settings -->
                <div class="space-y-4">
                    <Heading variant="small" title="SEO & Social" description="Optimize search engine and social media presence" />

                    <div class="grid gap-2">
                        <Label for="meta_title">Meta Title</Label>
                        <Input id="meta_title" v-model="form.meta_title" placeholder="SEO title (max 70 chars)" maxlength="70" />
                        <p class="text-xs text-muted-foreground">{{ (form.meta_title?.length ?? 0) }}/70 characters</p>
                        <InputError :message="form.errors.meta_title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="meta_description">Meta Description</Label>
                        <Textarea id="meta_description" v-model="form.meta_description" rows="2" placeholder="SEO description (max 160 chars)" maxlength="160" />
                        <p class="text-xs text-muted-foreground">{{ (form.meta_description?.length ?? 0) }}/160 characters</p>
                        <InputError :message="form.errors.meta_description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="og_title">OpenGraph Title</Label>
                        <Input id="og_title" v-model="form.og_title" placeholder="Social media share title" maxlength="70" />
                        <InputError :message="form.errors.og_title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="og_description">OpenGraph Description</Label>
                        <Textarea id="og_description" v-model="form.og_description" rows="2" placeholder="Social media share description" maxlength="200" />
                        <InputError :message="form.errors.og_description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="og_image">OpenGraph Image</Label>
                        <div v-if="ogImagePreview" class="relative">
                            <img :src="ogImagePreview" alt="OG preview" class="max-h-32 rounded-md object-cover" />
                            <Button type="button" variant="destructive" size="icon" class="absolute top-2 right-2 size-6" @click="removeOgImage">
                                <X class="size-3" />
                            </Button>
                        </div>
                        <Input id="og_image" type="file" accept="image/*" @change="handleOgImageChange" />
                        <p class="text-xs text-muted-foreground">Recommended size: 1200×630 pixels</p>
                        <InputError :message="form.errors.og_image" />
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <Button type="submit" :disabled="form.processing">Save Changes</Button>
                    <Button type="button" variant="destructive" @click="showDeleteDialog = true">
                        <Trash2 class="mr-2 size-4" />
                        Delete Article
                    </Button>
                </div>
            </form>

            <!-- Delete Confirmation -->
            <div v-if="showDeleteDialog" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="rounded-lg bg-background p-6 shadow-lg max-w-sm space-y-4">
                    <h3 class="font-semibold">Delete Article</h3>
                    <p class="text-sm text-muted-foreground">Are you sure you want to delete this article? This action cannot be undone.</p>
                    <div class="flex justify-end gap-2">
                        <Button variant="outline" @click="showDeleteDialog = false">Cancel</Button>
                        <Button variant="destructive" @click="deleteArticle">Delete</Button>
                    </div>
                </div>
            </div>

            <!-- Comments Section -->
            <div v-if="article.comments && article.comments.length > 0" class="space-y-4 border-t pt-8">
                <Heading variant="small" title="Comments" :description="`${article.comments.length} comment(s)`" />

                <div class="space-y-3">
                    <div v-for="comment in article.comments" :key="comment.id" class="rounded-lg border p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-medium">{{ comment.user?.name ?? 'Unknown' }}</span>
                                <span class="text-xs text-muted-foreground">{{ formatDate(comment.created_at) }}</span>
                                <Badge v-if="!comment.is_approved" variant="outline" class="text-xs">Pending</Badge>
                                <span v-if="comment.edited_at" class="text-xs text-muted-foreground">(edited)</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <Button v-if="!comment.is_approved" type="button" variant="ghost" size="icon" class="size-7" title="Approve" @click="approveComment(comment)">
                                    <Check class="size-3.5 text-green-600" />
                                </Button>
                                <Button type="button" variant="ghost" size="icon" class="size-7" title="Delete" @click="deleteComment(comment)">
                                    <Trash2 class="size-3.5 text-destructive" />
                                </Button>
                            </div>
                        </div>
                        <p class="text-sm">{{ comment.content }}</p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
