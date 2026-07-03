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

const STORAGE_KEY = "foma-cart-v1";
const MAX_QTY = 99;

function loadCart(): CartItem[] {
  try {
    const raw = window.localStorage.getItem(STORAGE_KEY);
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

export function CartProvider({ children }: { children: React.ReactNode }) {
  const [items, setItems] = useState<CartItem[]>([]);
  const hydrated = useRef(false);

  // Load once on mount (localStorage is unavailable during SSR).
  useEffect(() => {
    setItems(loadCart());
    hydrated.current = true;
  }, []);

  // Persist after hydration.
  useEffect(() => {
    if (!hydrated.current) return;
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    } catch {
      // storage full/blocked — cart still works in-memory
    }
  }, [items]);

  const addItem = useCallback(
    (item: Omit<CartItem, "quantity">, quantity = 1) => {
      setItems((prev) => {
        const existing = prev.find((i) => i.productId === item.productId);
        if (existing) {
          return prev.map((i) =>
            i.productId === item.productId
              ? { ...i, quantity: Math.min(i.quantity + quantity, MAX_QTY) }
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
