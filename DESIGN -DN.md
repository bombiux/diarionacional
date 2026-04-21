# Design System Specification: Editorial Precision & Digital Energy

## 1. Overview & Creative North Star: "The Architectural Pulse"
This design system is built to evolve a traditional news legacy into a high-velocity, digital-first powerhouse. Our Creative North Star is **"The Architectural Pulse."** We move away from the static, boxy templates of 2010s news sites toward a layout that feels like a living document—structured yet fluid.

The "Architectural" aspect comes from a rigid, crisp grid and the authoritative use of Manrope headlines. The "Pulse" is achieved through intentional asymmetry, vibrant secondary accents that signal category shifts (Tech, Viral, etc.), and a layering system that uses light and depth rather than lines. We are building a "Digital Curator" that feels "youthful" through its speed and color, yet "serious" through its impeccable typographic craft.

---

## 2. Colors: Tonal Depth over Structural Lines
We are moving away from the "grid-of-boxes" look. Color is our primary tool for defining space.

*   **Primary Identity:** We lead with `primary` (#355da8) and `primary_dim` (#27519b). These carry the weight of authority.
*   **The Vibrant Accents:** Use `secondary` (#006880) and `tertiary` (#b52900) to categorize content. Tech and high-energy news should leverage the `secondary_fixed_dim` and `tertiary_fixed` ranges to create a youthful pop against the sober primary blue.
*   **The "No-Line" Rule:** **Prohibit 1px solid borders for sectioning content.** Boundaries must be defined solely through background shifts. For example, a "Breaking News" section should use `surface_container_low` against a `surface` background.
*   **Surface Hierarchy & Nesting:** Treat the UI as physical layers of fine paper. 
    *   Base layer: `surface`.
    *   Secondary Content: `surface_container_low`.
    *   Interactive Cards: `surface_container_lowest`.
*   **The "Glass & Gradient" Rule:** For floating headers or category badges, use Glassmorphism. Apply `surface_container` at 80% opacity with a `backdrop-blur` of 12px. For main CTAs, use a subtle linear gradient from `primary` to `primary_dim` at a 135-degree angle to provide a premium "soul" that flat fills lack.

---

## 3. Typography: The Newsroom Refined
Our typography strategy pairs the geometric authority of **Manrope** for high-impact editorial moments with the utilitarian clarity of **Inter** for deep reading.

*   **Display & Headlines (Manrope):** Use `display-lg` to `headline-sm`. For headlines, apply a custom letter-spacing of `-0.02em`. This "tight tracking" mimics high-end print journalism and feels modern. Headlines should always be `Bold` or `ExtraBold`.
*   **Body (Inter):** Use `body-lg` (1rem) for article text to ensure readability. Inter provides the "Digital First" look. Keep tracking at `0` for body text to maintain legibility.
*   **Labels (Inter):** Use `label-md` and `label-sm` in `Medium` or `SemiBold` weights. These should often be all-caps with a slight letter-spacing of `0.05em` to differentiate them from body copy—perfect for category tags like "TECH" or "TRENDING."

---

## 4. Elevation & Depth: Tonal Layering
Traditional shadows are a last resort. We define hierarchy through the **Layering Principle**.

*   **Ambient Shadows:** When a card must float (e.g., a featured story), use an extra-diffused shadow: `box-shadow: 0 12px 32px -4px rgba(32, 50, 86, 0.08)`. Note the color: we use a tinted `on_surface` (navy-based) rather than pure black to keep the UI looking "expensive."
*   **The "Ghost Border" Fallback:** If accessibility requires a border, use `outline_variant` at 15% opacity. It should be felt, not seen.
*   **Interaction States:** On hover, instead of a heavy shadow, transition the surface color from `surface_container_low` to `surface_container_high`. This subtle "lift" feels more sophisticated and less cluttered.

---

## 5. Components

### Buttons
*   **Primary:** Solid `primary` fill, `on_primary` text. No border. Roundedness: `DEFAULT` (0.5rem).
*   **Secondary:** `surface_container_highest` fill with `primary` text. This creates a soft, modern look that doesn't compete with the main CTA.
*   **Tertiary:** Ghost style. No background. Use `primary` text with an underline that appears only on hover.

### Cards & News Feed
*   **The Rule of Zero Dividers:** Never use horizontal lines to separate news items. Use vertical white space (32px or 48px from our spacing scale) or alternate background tints (`surface` vs `surface_container_lowest`).
*   **Imagery:** All images should have a `DEFAULT` (0.5rem) corner radius. For "Viral" categories, images can break the grid slightly, overlapping the text container to create energy.

### Chips & Tags
*   **Category Chips:** Use `secondary_container` for the background and `on_secondary_container` for text. For high-energy categories, use `tertiary_container`. 
*   **Shape:** Use `full` roundedness (pill shape) to contrast against the architectural squareness of the grid.

### Input Fields
*   **Style:** Minimalist. Use `surface_container_low` as the background fill. No bottom line or full border. On focus, transition the background to `surface_container_high` and add a 2px `primary` left-border "accent" to signal activity.

---

## 6. Do’s and Don'ts

### Do:
*   **Embrace Asymmetry:** Let a featured image take up 7 columns of a 12-column grid, leaving the rest for whitespace or a floating headline.
*   **Use High Contrast:** Pair a `display-lg` headline in `on_surface` with a `label-md` category in a vibrant `tertiary` red.
*   **Prioritize Breathing Room:** If you think there is enough whitespace, add 16px more. High-end editorial requires room to breathe.

### Don't:
*   **No "Box-in-Box" Design:** Avoid putting a card inside a container that also has a border. Use color shifts.
*   **Avoid Pure Black:** Use `on_surface` (#203256) for text. It’s a deep, authoritative navy that feels more premium than #000.
*   **No Default Shadows:** Never use the standard CSS `0 2px 4px rgba(0,0,0,0.5)`. It kills the "Digital First" energy. Use our tinted Ambient Shadows.
*   **No Crowding:** Do not use `title-sm` for long-form reading. Keep titles for headers and `body-lg` for the story.