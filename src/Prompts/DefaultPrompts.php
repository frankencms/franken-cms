<?php

namespace FrankenCms\Prompts;

class DefaultPrompts
{
    /**
     * Get default prompt for SEO Title
     */
    public static function seoTitle(): string
    {
        return <<<'PROMPT'
You are writing an SEO title tag for a blog post.

INPUT
- Original Title: {title}
- Post Content: {content}

TASK
Write 1 SEO-optimized title (50–60 characters total) that:
- Includes 1–2 primary keywords from the title or content naturally
- Clearly conveys the topic or benefit
- Is compelling and readable in active voice
- Balances clarity, curiosity, and keyword relevance
- Encourages clicks without clickbait or exaggeration

STYLE RULES
- Title Case (Capitalize Major Words)
- No punctuation at the end (no periods or exclamation marks)
- No emojis, brackets, or hashtags
- Avoid all caps or keyword stuffing
- Must sound professional and trustworthy

OUTPUT FORMAT
Return only the final SEO title text.
PROMPT;
    }

    /**
     * Get default prompt for SEO Description
     */
    public static function seoDescription(): string
    {
        return <<<'PROMPT'
You are writing a Google SEO meta description.

INPUT
- Title: {title}
- Content: {content}

TASK
Write 1 meta description (150–160 characters total) that:
- Clearly summarizes the core value or topic of the post
- Includes 1–2 natural keywords from the title or content
- Uses active voice and encourages action or curiosity
- Reads smoothly in a single sentence
- Ends cleanly without punctuation clutter

STYLE RULES
- No hashtags, emojis, or special characters
- Avoid repetition or keyword stuffing
- Use plain text only (no quotes or labels)
- Professional, natural tone focused on clarity and engagement

OUTPUT FORMAT
Return only the final meta description text.
PROMPT;
    }

    /**
     * Get default prompt for Post Teaser/Excerpt
     */
    public static function teaser(): string
    {
        return <<<'PROMPT'
You are generating a short teaser (excerpt) for a blog post.

INPUT
- Post Title: {title}
- Post Content: {content}

TASK
Write 1 teaser paragraph (2–3 sentences, ~150 characters total) that:
- Grabs attention within the first few words
- Summarizes the main insight or benefit of the post
- Creates curiosity to read more
- Uses a natural, conversational tone
- Includes one relevant keyword or phrase from the post when possible

STYLE RULES
- No salesy language or exaggeration
- Avoid clickbait phrases (“you won’t believe,” “game-changer,” etc.)
- Write in sentence case (capitalize only the first letter and proper nouns)
- No hashtags, emojis, or quotes
- Plain text only — do not label it “Teaser” or include metadata

OUTPUT FORMAT
Return only the final teaser text.
PROMPT;
    }

    /**
     * Get default prompt for Image Alt Text
     */
    public static function altText(): string
    {
        return <<<'PROMPT'
You are generating alt text for an image to improve accessibility and SEO.

INPUT
- Post Title: {title}
- Post Content : {content}
- Image Filename: {filename}

TASK
Write 1 short, clear sentence describing what appears in the image, using the provided context to infer meaning.

RULES
- Maximum length: 125 characters
- Describe the visible subject and any important context (e.g., actions, setting, tone)
- Include one naturally fitting keyword from the post title or content if relevant
- Use sentence case (capitalize first word only)
- No punctuation at the end unless required for clarity
- Avoid “image of,” “picture of,” or redundant phrasing
- Professional, neutral tone (no speculation or emotion)
- No clickbait, hashtags, or filler text
- No double quotes

ACCESSIBILITY FOCUS
Ensure the alt text conveys the essential visual information a sighted user would get from the image.

DISAMBIGUATION
- If the filename suggests a product, event, or location, use it only if helpful.
- If context is unclear, describe the main visible subject neutrally.

OUTPUT FORMAT
Return only the final alt text string — no labels or quotes.
PROMPT;
    }

    /**
     * Get default prompt for Image Title
     */
    public static function imageTitle(): string
    {
        return <<<'PROMPT'
You are generating a single HTML image title (hover text).

INPUT
- Post Title: {title}
- Post Content : {content}
- Image Filename: {filename}

TASK
Write 1 concise, descriptive title for the image that:
- Is 3–7 words and ≤ 60 characters
- Adds context beyond the visible image (ties to the post topic)
- Sounds professional and specific
- Naturally includes one relevant term from the Post Title/Content if it fits
- Uses Title Case (Capitalize Major Words)

STYLE RULES (MUST)
- Plain text only (no quotes, punctuation at end, emojis, hashtags, or brackets)
- No clickbait, no stuffing, no salesy language
- No private/sensitive info; no speculation

DISAMBIGUATION
- If the filename reveals a product/model/scene that helps clarity, you may include it
- If context is insufficient, fall back to a neutral, on-topic descriptor based on the Post Title

OUTPUT FORMAT
Return only the final title text with nothing else.
PROMPT;
    }

    /**
     * Get default prompt for Full Blog Post
     */
    public static function blogPost(): string
    {
        return <<<'PROMPT'
You are an expert SEO content writer.

INPUT
- Title: {title}
- Topic / Focus: {focus}
- Target Audience: {audience}
- Key Points or Notes: {content}

TASK
Write a complete, high-quality blog post that:
- Is well-structured with clear headings (H2/H3)
- Uses engaging, natural language in active voice
- Integrates relevant keywords naturally throughout
- Educates, informs, or entertains the reader depending on the topic
- Flows logically from intro to conclusion
- Ends with a concise, motivating summary or takeaway

SEO RULES
- Optimize for a single primary keyword and 2–3 secondary keywords
- Include the primary keyword in the first paragraph and at least one subheading
- Use short paragraphs (2–4 sentences) and scannable formatting
- Add transition phrases to improve readability and NLP comprehension
- Avoid keyword stuffing, repetition, or filler content

STYLE & TONE
- Professional yet conversational
- Confident, informative, and trustworthy
- Match the audience's level of expertise
- Avoid buzzwords, clichés, and vague statements

OUTPUT FORMAT
- Markdown structure with headings, subheadings, and bullet points
- No meta descriptions, no SEO titles, no tags — just the full post content

QUALITY TARGET
Write between 800–1,200 words of cohesive, original content.
PROMPT;
    }
}
