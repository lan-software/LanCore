<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import { ThumbsUp, ThumbsDown, Calendar, User, ArrowLeft } from 'lucide-vue-next'
import { computed } from 'vue'
import { store as storeComment, vote } from '@/actions/App/Domain/News/Http/Controllers/NewsCommentController'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Textarea } from '@/components/ui/textarea'
import type { NewsArticle, NewsComment } from '@/types/domain'

const props = defineProps<{
    article: NewsArticle
    comments: NewsComment[]
}>()

const page = usePage()

const isAuthenticated = computed(() => !!page.props.auth?.user)

const commentForm = useForm({
    content: '',
})

function submitComment() {
    commentForm.post(storeComment({ newsArticle: props.article.id }).url, {
        preserveScroll: true,
        onSuccess: () => {
            commentForm.reset()
        },
    })
}

function voteComment(commentId: number, value: number) {
    router.post(vote({ newsComment: commentId }).url, { value }, { preserveScroll: true })
}

function formatDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
    })
}

function formatCommentDate(dateString: string): string {
    return new Date(dateString).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    })
}

const ogTitle = computed(() => props.article.og_title || props.article.meta_title || props.article.title)
const ogDescription = computed(() => props.article.og_description || props.article.meta_description || props.article.summary || '')
const ogImage = computed(() => props.article.og_image_url || props.article.image_url || '')
const metaTitle = computed(() => props.article.meta_title || props.article.title)
const metaDescription = computed(() => props.article.meta_description || props.article.summary || '')
</script>

<template>
    <Head :title="metaTitle">
        <meta name="description" :content="metaDescription" />
        <meta name="keywords" :content="(article.tags ?? []).join(', ')" />

        <!-- OpenGraph Protocol -->
        <meta property="og:type" content="article" />
        <meta property="og:title" :content="ogTitle" />
        <meta property="og:description" :content="ogDescription" />
        <meta v-if="ogImage" property="og:image" :content="ogImage" />
        <meta property="og:url" :content="`/news/${article.slug}`" />
        <meta v-if="article.published_at" property="article:published_time" :content="article.published_at" />
        <meta v-if="article.author" property="article:author" :content="article.author.name" />
        <meta v-for="tag in article.tags" :key="tag" property="article:tag" :content="tag" />

        <!-- Twitter Card -->
        <meta name="twitter:card" content="summary_large_image" />
        <meta name="twitter:title" :content="ogTitle" />
        <meta name="twitter:description" :content="ogDescription" />
        <meta v-if="ogImage" name="twitter:image" :content="ogImage" />
    </Head>

    <div class="flex min-h-screen flex-col bg-background text-foreground">
        <!-- Header -->
        <header class="border-b">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-6 py-4">
                <Link href="/" class="text-lg font-semibold">LanCore</Link>
                <nav class="flex items-center gap-4">
                    <Link v-if="$page.props.auth?.user" href="/dashboard" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
                        Dashboard
                    </Link>
                    <template v-else>
                        <Link href="/login" class="text-sm text-muted-foreground hover:text-foreground">Log in</Link>
                        <Link href="/register" class="rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">Register</Link>
                    </template>
                </nav>
            </div>
        </header>

        <!-- Article -->
        <main class="flex-1">
            <article class="mx-auto max-w-4xl px-6 py-12">
                <!-- Back Link -->
                <Link href="/" class="inline-flex items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground mb-8">
                    <ArrowLeft class="size-3.5" />
                    Back to Home
                </Link>

                <!-- Banner Image -->
                <img v-if="article.image_url" :src="article.image_url" :alt="article.title" class="w-full max-h-96 object-cover rounded-lg mb-8" />

                <!-- Article Header -->
                <header class="space-y-4 mb-8">
                    <h1 class="text-4xl font-bold tracking-tight">{{ article.title }}</h1>
                    <div class="flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
                        <span v-if="article.author" class="inline-flex items-center gap-1.5">
                            <User class="size-3.5" />
                            {{ article.author.name }}
                        </span>
                        <span v-if="article.published_at" class="inline-flex items-center gap-1.5">
                            <Calendar class="size-3.5" />
                            {{ formatDate(article.published_at) }}
                        </span>
                    </div>
                    <div v-if="article.tags && article.tags.length > 0" class="flex flex-wrap gap-1.5">
                        <Badge v-for="tag in article.tags" :key="tag" variant="secondary" class="text-xs">{{ tag }}</Badge>
                    </div>
                </header>

                <!-- Summary -->
                <p v-if="article.summary" class="text-lg text-muted-foreground mb-8 border-l-4 border-primary pl-4 italic">
                    {{ article.summary }}
                </p>

                <!-- Content (Rich Text) -->
                <div class="prose prose-lg dark:prose-invert max-w-none" v-html="article.content" />

                <!-- Comments Section -->
                <section v-if="article.comments_enabled" class="mt-16 border-t pt-8">
                    <h2 class="text-2xl font-bold mb-6">Comments</h2>

                    <!-- Comment Form -->
                    <div v-if="isAuthenticated" class="mb-8 space-y-3">
                        <Textarea
                            v-model="commentForm.content"
                            rows="3"
                            placeholder="Write a comment…"
                        />
                        <div class="flex items-center justify-between">
                            <p v-if="commentForm.errors.content" class="text-sm text-destructive">{{ commentForm.errors.content }}</p>
                            <Button :disabled="commentForm.processing || !commentForm.content.trim()" @click="submitComment">
                                Post Comment
                            </Button>
                        </div>
                    </div>
                    <div v-else class="mb-8 rounded-lg border p-4 text-center">
                        <p class="text-sm text-muted-foreground">
                            <Link href="/login" class="font-medium text-foreground hover:underline">Log in</Link>
                            to leave a comment.
                        </p>
                    </div>

                    <!-- Comments List -->
                    <div v-if="comments.length > 0" class="space-y-4">
                        <div v-for="comment in comments" :key="comment.id" class="rounded-lg border p-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-medium">{{ comment.user?.name ?? 'Unknown' }}</span>
                                    <span class="text-xs text-muted-foreground">{{ formatCommentDate(comment.created_at) }}</span>
                                    <span v-if="comment.edited_at" class="text-xs text-muted-foreground">(edited)</span>
                                </div>
                            </div>
                            <p class="text-sm">{{ comment.content }}</p>
                            <div v-if="isAuthenticated" class="flex items-center gap-2 pt-1">
                                <Button variant="ghost" size="sm" class="h-7 px-2 text-xs" @click="voteComment(comment.id, 1)">
                                    <ThumbsUp class="mr-1 size-3" />
                                </Button>
                                <span class="text-xs font-medium" :class="{ 'text-green-600': (comment.vote_score ?? 0) > 0, 'text-red-600': (comment.vote_score ?? 0) < 0 }">
                                    {{ comment.vote_score ?? 0 }}
                                </span>
                                <Button variant="ghost" size="sm" class="h-7 px-2 text-xs" @click="voteComment(comment.id, -1)">
                                    <ThumbsDown class="mr-1 size-3" />
                                </Button>
                            </div>
                        </div>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">No comments yet. Be the first to comment!</p>
                </section>
            </article>
        </main>

        <!-- Footer -->
        <footer class="border-t py-6">
            <div class="mx-auto max-w-4xl px-6 text-center text-sm text-muted-foreground">
                &copy; {{ new Date().getFullYear() }} LanCore
            </div>
        </footer>
    </div>
</template>
