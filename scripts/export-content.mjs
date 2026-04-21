#!/usr/bin/env node

/**
 * Export content from production WordPress site via REST API.
 *
 * Usage: npm run export:content
 * Output: data/export.json
 */

import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Allow overriding source URL via env: WP_SOURCE=https://diariobarricada.com
const SOURCE = process.env.WP_SOURCE || 'https://diariobarricada.com';
const POSTS_PER_PAGE = 20;
const RATE_LIMIT_MS = 200; // ms between requests
const IMAGE_CUTOFF_DAYS = 7;

// Allow insecure TLS for local dev with self-signed certs
if (process.env.NODE_TLS_REJECT_UNAUTHORIZED === '0') {
  process.env.NODE_TLS_REJECT_UNAUTHORIZED = '0';
}

/**
 * Sleep for ms milliseconds.
 */
function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Fetch JSON from a URL with retry logic.
 */
async function fetchJSON(url, retries = 3) {
  for (let attempt = 1; attempt <= retries; attempt++) {
    try {
      const response = await fetch(url, {
        headers: { Accept: 'application/json' },
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status} for ${url}`);
      }

      // Handle pagination headers
      const totalPages = parseInt(response.headers.get('x-wp-totalpages') || '1', 10);
      const data = await response.json();

      return { data, totalPages };
    } catch (err) {
      if (attempt === retries) throw err;
      console.warn(`  ⚠️  Retry ${attempt}/${retries} for ${url}`);
      await sleep(1000 * attempt);
    }
  }
}

/**
 * Fetch all categories.
 */
async function fetchCategories() {
  console.log('📂 Fetching categories...');
  const { data } = await fetchJSON(
    `${SOURCE}/wp-json/wp/v2/categories?per_page=100`
  );

  return data.map((cat) => ({
    source_id: cat.id,
    name: cat.name,
    slug: cat.slug,
    parent: cat.parent,
  }));
}

/**
 * Fetch unique authors from the already-collected posts.
 */
async function fetchAuthors(posts) {
  console.log('👤 Fetching authors...');
  const authorMap = new Map();

  // Extract unique author IDs from all exported posts
  const authorIds = [...new Set(posts.map((p) => p.author_id))];

  for (const authorId of authorIds) {
    await sleep(RATE_LIMIT_MS);
    const { data: user } = await fetchJSON(
      `${SOURCE}/wp-json/wp/v2/users/${authorId}?_fields=id,name,slug`
    );

    if (user) {
      authorMap.set(user.id, {
        source_id: user.id,
        name: user.name,
        slug: user.slug,
      });
    }
  }

  return [...authorMap.values()];
}

/**
 * Fetch posts for a given category (single request, POSTS_PER_PAGE results).
 */
async function fetchPostsForCategory(categoryId) {
  const { data } = await fetchJSON(
    `${SOURCE}/wp-json/wp/v2/posts?categories=${categoryId}&per_page=${POSTS_PER_PAGE}&_embed=wp:featuredmedia,wp:term`
  );
  await sleep(RATE_LIMIT_MS);
  return data;
}

/**
 * Determine image strategy based on post date.
 */
function getImageStrategy(postDate) {
  const cutoffDate = new Date();
  cutoffDate.setDate(cutoffDate.getDate() - IMAGE_CUTOFF_DAYS);

  const postDateTime = new Date(postDate);
  return postDateTime >= cutoffDate ? 'download' : 'fallback';
}

/**
 * Extract featured image URL from embedded data.
 */
function getFeaturedImageUrl(post) {
  const embedded = post._embedded || {};
  const media = embedded['wp:featuredmedia'] || [];

  if (media.length > 0 && media[0].source_url) {
    return media[0].source_url;
  }

  return null;
}

/**
 * Extract category slugs from embedded terms.
 */
function getCategorySlugs(post) {
  const embedded = post._embedded || {};
  const terms = embedded['wp:term'] || [];

  // First element is usually categories, second is tags
  const categories = terms[0] || [];
  return categories.map((term) => term.slug).filter(Boolean);
}

/**
 * Extract tag names from embedded terms.
 */
function getTagNames(post) {
  const embedded = post._embedded || {};
  const terms = embedded['wp:term'] || [];

  // Second element is tags
  const tags = terms[1] || [];
  return tags.map((term) => term.name).filter(Boolean);
}

/**
 * Main export function.
 */
async function main() {
  console.log(`🚀 Exporting content from ${SOURCE}`);
  console.log(`📅 Image cutoff: ${IMAGE_CUTOFF_DAYS} days ago`);

  // 1. Fetch categories
  const categories = await fetchCategories();
  console.log(`✅ Found ${categories.length} categories`);

  // 2. Fetch posts for each category, deduplicating
  console.log('📝 Fetching posts...');
  const postMap = new Map();

  for (const category of categories) {
    console.log(`  📂 ${category.name} (${category.slug})`);

    const posts = await fetchPostsForCategory(category.source_id);

    for (const post of posts) {
      if (!postMap.has(post.id)) {
        postMap.set(post.id, {
          source_id: post.id,
          title: post.title?.rendered || '',
          content: post.content?.rendered || '',
          excerpt: post.excerpt?.rendered || '',
          slug: post.slug,
          date: post.date,
          status: post.status,
          author_id: post.author,
          featured_image_url: null,
          image_strategy: 'fallback',
          category_slugs: [],
          tag_names: [],
        });
      }

      // Merge category slugs
      const existing = postMap.get(post.id);
      const slugs = getCategorySlugs(post);
      for (const slug of slugs) {
        if (!existing.category_slugs.includes(slug)) {
          existing.category_slugs.push(slug);
        }
      }

      // Merge tags (take from latest occurrence)
      const tags = getTagNames(post);
      if (tags.length > 0) {
        existing.tag_names = [...new Set([...existing.tag_names, ...tags])];
      }

      // Featured image
      if (!existing.featured_image_url) {
        existing.featured_image_url = getFeaturedImageUrl(post);
        existing.image_strategy = getImageStrategy(post.date);
      }
    }
  }

  const posts = [...postMap.values()];
  console.log(`✅ Found ${posts.length} unique posts`);

  // 3. Fetch authors from all collected posts
  const authors = await fetchAuthors(posts);
  console.log(`✅ Found ${authors.length} authors`);

  // 4. Build export object
  const cutoffDate = new Date();
  cutoffDate.setDate(cutoffDate.getDate() - IMAGE_CUTOFF_DAYS);

  const exportData = {
    exported_at: new Date().toISOString(),
    source: SOURCE,
    image_cutoff_date: cutoffDate.toISOString(),
    categories,
    authors,
    posts,
  };

  // 5. Write to file
  const outputDir = path.resolve(__dirname, '..', 'data');
  const outputFile = path.join(outputDir, 'export.json');

  fs.mkdirSync(outputDir, { recursive: true });
  fs.writeFileSync(outputFile, JSON.stringify(exportData, null, 2), 'utf-8');

  console.log(`\n✅ Export complete!`);
  console.log(`📁 Saved to: ${outputFile}`);
  console.log(`📊 Stats:`);
  console.log(`   Categories: ${categories.length}`);
  console.log(`   Authors: ${authors.length}`);
  console.log(`   Posts: ${posts.length}`);

  const downloadCount = posts.filter((p) => p.image_strategy === 'download').length;
  const fallbackCount = posts.filter((p) => p.image_strategy === 'fallback').length;
  console.log(`   Images to download: ${downloadCount}`);
  console.log(`   Images using fallback: ${fallbackCount}`);
}

main().catch((err) => {
  console.error('❌ Export failed:', err.message);
  process.exit(1);
});
