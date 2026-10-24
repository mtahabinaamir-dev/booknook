# BookNook — Design Spec

## Brand
- **Name:** BookNook
- **Personality:** A small, friendly neighbourhood bookshop — warm paper, ink and leather; calm, trustworthy, no visual noise.
- **Logo:** Text only, no image. The word "BookNook" in the serif display stack, "Book" in ink color, "Nook" in accent color, followed by a small accent period: `BookNook.` No icon font, no SVG logo.
- **Currency:** Prices are shown as `$8.95` (dollar prefix, two decimals).

## Palette
All colors are CSS custom properties on `:root`. One accent only.

| Token | Hex | Use |
|---|---|---|
| `--paper` | `#FAF6EF` | Page background |
| `--surface` | `#FFFFFF` | Cards, forms, table rows |
| `--surface-2` | `#F3ECDF` | Hero strip, table header, zebra rows |
| `--ink` | `#2B2118` | Body text, headings (~14.5:1 on paper) |
| `--muted` | `#6B5C4D` | Secondary text, authors, meta (~5.6:1 on paper — AA) |
| `--accent` | `#B4502A` | Primary buttons, logo "Nook", links (white text on it is 5.1:1 — AA) |
| `--accent-dark` | `#93401F` | Accent hover/active |
| `--border` | `#E2D7C3` | Card borders, input borders, table rules |
| `--success-bg` / `--success` | `#E9F3E6` / `#2F6B2F` | Success alert |
| `--error-bg` / `--error` | `#F9E9E6` / `#A93226` | Error alert, remove links |

### Cover colors (decorative)
Book covers are a solid block with the title's first letter in large serif. Cycle these six by book id (`id % 6`): `#B4502A`, `#46604C`, `#37455C`, `#64404E`, `#8C5A2E`, `#586A72`. Title text always appears below the cover too, so the cover letter is decoration, not information.

## Typography
System fonts only — no webfonts, no downloads.
- **Display / logo / book titles:** `Georgia, "Times New Roman", Times, serif`
- **Body / UI:** `-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif`
- **Sizes:** h1 `2rem`, h2 `1.375rem`, card title `1.0625rem`, body `1rem` (line-height 1.55), price `1.125rem` bold, small/meta `0.875rem`.

## Layout — every page
- **Header** (white surface, bottom border): logo left; search box center (input + "Search" button, submits GET to home); nav right: `Home`, `Cart`, then `Login` / `Register` for guests or `My Orders` / `Logout` when signed in, plus `Admin` link for admins only.
- **Container:** max-width `1080px`, `20px` side padding, centered.
- **Footer** (surface-2, top border, muted small text): `BookNook — a small online bookstore.` left, `Built with PHP & MySQL` right.

### Pages
- **Home (`index.php`):** Hero strip (surface-2, h1 "Find your next book." + one line of muted text). Optional category line under hero if categories exist. Then the book grid; a search query or category filter just narrows the same grid and changes the h2 to `Results for "…"` / the category name.
- **Book detail (`book.php`):** Two columns on desktop (cover block left ~260px, details right), stacked on phone. Details: title (h1), author (muted), price, stock badge, description paragraph, quantity-less `Add to Cart` button, back link to Home.
- **Cart (`cart.php`):** Table — Book (cover initial chip + title), Unit price, Qty (number input + Update button per whole cart), Line total, Remove link. Below right-aligned: grand total + `Proceed to Checkout` primary button + `Continue shopping` ghost link.
- **Checkout (`checkout.php`):** One form: full name, phone, address (textarea), payment note "Cash on Delivery" as static text, order summary box (lines + total) above the form, `Place Order` button. On success, redirect to My Orders with a success alert.
- **Login (`login.php`) / Register (`register.php`):** Centered narrow card (max-width `420px`), h1, labeled fields, full-width primary button, one muted line linking to the other page ("New here? Create an account").
- **My Orders (`orders.php`):** Table — Order #, Date, Items (title × qty lines), Total, Status badge. Newest first.
- **Admin (`admin/`):** Same header. Books section: add/edit book form (title, author, category, price, stock, description) above a table of all books with Edit / Delete links. Orders section: table of all orders with a status select + Update button per row.

## Components
- **Buttons:** `.btn` — padding `9px 18px`, radius `8px`, 1px border. `.btn-primary` accent bg, white text, hover accent-dark. `.btn-ghost` transparent bg, ink text, border color; hover surface-2. Buttons are real `<button>` or `<a class="btn">`.
- **Inputs:** full-width in forms, padding `9px 12px`, border `--border`, radius `8px`, white bg; focus = 2px accent outline (no shadow). Labels above inputs, `0.875rem`, bold-ish (600).
- **Book card:** white surface, 1px border, radius `12px`, padding `14px`. Cover block (aspect 3/4, radius `8px`, centered serif initial at `3.5rem` in `#FAF3E3`). Title (serif, 2-line clamp not required), author muted small, row at bottom: price left, `Add` primary button right.
- **Badges:** small pill, radius `999px`, padding `2px 10px`, `0.8125rem`. Stock: `In stock` (success colors), `Low stock` (surface-2 bg, accent-dark text), `Out of stock` (error colors) — and the Add button is disabled when out of stock. Order status badge reuses the same pill with success/muted/error colors per status.
- **Alerts:** full-width of the container, padding `12px 16px`, radius `8px`, success/error bg + text colors above. One per page, directly under the header.
- **Empty states:** centered block, padding `48px 20px`, muted text, one friendly line + one action button:
  - No books / no results: "No books found." + `Browse all books` button (clears search).
  - Empty cart: "Your cart is empty." + `Browse books` button.
  - No orders: "You haven't placed any orders yet." + `Browse books` button.

## Rules for the developer
1. **One CSS file** (`assets/style.css`) for the whole site, admin included. Palette only via the custom properties above — no hex values repeated in rules.
2. **No JavaScript required** for any core flow (browse, search, cart, checkout, admin). JS may only be progressive enhancement (e.g. a delete confirmation); every action must work with JS off.
3. **No external assets:** no webfonts, no image files, no CDN links. Covers are colored `<div>`s with a letter, per the cover palette.
4. **Responsive:** book grid uses `repeat(auto-fill, minmax(200px, 1fr))`. Under `600px`: grid becomes 1 column, header stacks (logo row, then search full-width, then nav), book detail stacks, cart/checkout tables scroll horizontally inside a wrapper (`overflow-x:auto`) rather than breaking layout.
5. **Semantics:** one `<h1>` per page, real `<table>` for cart/orders/admin lists, `<label for>` on every input, buttons for actions and links for navigation.
6. Follow the house rules: minimum code that fully works, single-line comments only.
