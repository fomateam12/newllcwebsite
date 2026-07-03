"use client";

import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useRef,
  useState,
} from "react";
import {
  readCartCookieClient,
  writeCartCookieClient,
  type CompactCartItem,
} from "@/lib/cart-cookie";

export type CartItem = {
  productId: number;
  slug: string;
  name: string;
  /** display price in dollars — checkout re-prices server-side from D1 */
  unitPrice: number;
  /** resolved image URL (may be null) */
  image: string | null;
  quantity: number;
  customizationData?: unknown;
};

type CartContextValue = {
  items: CartItem[];
  count: number;
  subtotal: number;
  addItem: (item: Omit<CartItem, "quantity">, quantity?: number) => void;
  setQuantity: (productId: number, quantity: number) => void;
  removeItem: (productId: number) => void;
  clear: () => void;
};

const CartContext = createContext<CartContextValue | null>(null);

/*
 * PERSISTENCE (see lib/cart-cookie.ts for the full design note):
 *
 *  - The COOKIE is the canonical persisted cart: compact
 *    {productId, quantity, customization} that server components on
 *    Workers can read during SSR. It decides WHICH items exist and their
 *    quantities.
 *  - localStorage is only a cosmetic DISPLAY CACHE (name/slug/price/image
 *    keyed by productId — too heavy for the 4 KB cookie). If it's missing
 *    for a cookie item we degrade to a placeholder instead of dropping
 *    the item; the server would re-join this data from D1 anyway.
 */
const DISPLAY_CACHE_KEY = "foma-cart-display-v2";
const LEGACY_STORAGE_KEY = "foma-cart-v1"; // pre-cookie carts — migrated once
const MAX_QTY = 99;

type DisplayEntry = {
  slug: string;
  name: string;
  unitPrice: number;
  image: string | null;
  /** full (untrimmed) customization — the cookie copy may be truncated */
  customizationData?: unknown;
};

function readDisplayCache(): Record<string, DisplayEntry> {
  try {
    const raw = window.localStorage.getItem(DISPLAY_CACHE_KEY);
    const parsed = raw ? JSON.parse(raw) : null;
    return parsed && typeof parsed === "object" ? parsed : {};
  } catch {
    return {};
  }
}

function readLegacyCart(): CartItem[] {
  try {
    const raw = window.localStorage.getItem(LEGACY_STORAGE_KEY);
    if (!raw) return [];
    const parsed = JSON.parse(raw);
    if (!Array.isArray(parsed)) return [];
    return parsed.filter(
      (i): i is CartItem =>
        typeof i === "object" && i !== null &&
        Number.isInteger(i.productId) &&
        typeof i.slug === "string" &&
        typeof i.name === "string" &&
        typeof i.unitPrice === "number" &&
        Number.isInteger(i.quantity) && i.quantity >= 1,
    );
  } catch {
    return [];
  }
}

/** Join the compact cookie cart with the display cache into full items. */
function hydrateItems(
  compact: CompactCartItem[],
  cache: Record<string, DisplayEntry>,
): CartItem[] {
  return compact.map((c) => {
    const display = cache[String(c.p)];
    return {
      productId: c.p,
      quantity: Math.min(c.q, MAX_QTY),
      slug: display?.slug ?? "",
      name: display?.name ?? `Saved item #${c.p}`,
      unitPrice: display?.unitPrice ?? 0,
      image: display?.image ?? null,
      ...(display?.customizationData !== undefined
        ? { customizationData: display.customizationData }
        : c.c !== undefined
          ? { customizationData: c.c }
          : {}),
    };
  });
}

function loadCart(): CartItem[] {
  const compact = readCartCookieClient();
  if (compact.length > 0) {
    return hydrateItems(compact, readDisplayCache());
  }
  // One-time migration: carts saved before the cookie switch.
  const legacy = readLegacyCart();
  if (legacy.length > 0) {
    try {
      window.localStorage.removeItem(LEGACY_STORAGE_KEY);
    } catch {
      /* ignore */
    }
  }
  return legacy;
}

function persistCart(items: CartItem[]): void {
  // 1) Canonical compact cookie (server-readable).
  writeCartCookieClient(items);
  // 2) Display cache (client-only cosmetics + full customization).
  try {
    const cache: Record<string, DisplayEntry> = {};
    for (const i of items) {
      cache[String(i.productId)] = {
        slug: i.slug,
        name: i.name,
        unitPrice: i.unitPrice,
        image: i.image,
        ...(i.customizationData !== undefined
          ? { customizationData: i.customizationData }
          : {}),
      };
    }
    window.localStorage.setItem(DISPLAY_CACHE_KEY, JSON.stringify(cache));
  } catch {
    // storage full/blocked — cookie still persisted, cart works in-memory
  }
}

export function CartProvider({ children }: { children: React.ReactNode }) {
  const [items, setItems] = useState<CartItem[]>([]);
  const hydrated = useRef(false);

  // Load once on mount (document.cookie is unavailable during SSR render).
  useEffect(() => {
    setItems(loadCart());
    hydrated.current = true;
  }, []);

  // Persist after hydration.
  useEffect(() => {
    if (!hydrated.current) return;
    persistCart(items);
    // TODO(abandoned-cart): this is the attach point for abandoned-cart
    // email groundwork. When a cart becomes non-empty and the visitor has
    // identified themselves (email captured on /checkout), debounce-post
    // {email, cart cookie payload} to a future /api/cart-snapshot endpoint
    // that upserts into the drafts/carts.sql table; a scheduled Worker then
    // sends the Resend reminder (lib/email.ts) for carts idle > 24h.
  }, [items]);

  const addItem = useCallback(
    (item: Omit<CartItem, "quantity">, quantity = 1) => {
      setItems((prev) => {
        const existing = prev.find((i) => i.productId === item.productId);
        if (existing) {
          return prev.map((i) =>
            i.productId === item.productId
              ? {
                  ...i,
                  ...item, // refresh display data + customization
                  quantity: Math.min(i.quantity + quantity, MAX_QTY),
                }
              : i,
          );
        }
        return [...prev, { ...item, quantity: Math.min(quantity, MAX_QTY) }];
      });
    },
    [],
  );

  const setQuantity = useCallback((productId: number, quantity: number) => {
    setItems((prev) =>
      quantity < 1
        ? prev.filter((i) => i.productId !== productId)
        : prev.map((i) =>
            i.productId === productId
              ? { ...i, quantity: Math.min(quantity, MAX_QTY) }
              : i,
          ),
    );
  }, []);

  const removeItem = useCallback((productId: number) => {
    setItems((prev) => prev.filter((i) => i.productId !== productId));
  }, []);

  const clear = useCallback(() => setItems([]), []);

  const value = useMemo<CartContextValue>(() => {
    const count = items.reduce((n, i) => n + i.quantity, 0);
    const subtotal = items.reduce((s, i) => s + i.unitPrice * i.quantity, 0);
    return { items, count, subtotal, addItem, setQuantity, removeItem, clear };
  }, [items, addItem, setQuantity, removeItem, clear]);

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart(): CartContextValue {
  const ctx = useContext(CartContext);
  if (!ctx) throw new Error("useCart must be used inside <CartProvider>");
  return ctx;
}
