import { sql } from "drizzle-orm";
import {
  index,
  integer,
  real,
  sqliteTable,
  text,
} from "drizzle-orm/sqlite-core";

export const categories = sqliteTable("categories", {
  id: integer("id").primaryKey({ autoIncrement: true }),
  name: text("name").notNull(),
  slug: text("slug").notNull().unique(),
  parentId: integer("parent_id"),
  sortOrder: integer("sort_order").notNull().default(0),
  imageUrl: text("image_url"),
  createdAt: text("created_at")
    .notNull()
    .default(sql`(datetime('now'))`),
});

export const products = sqliteTable(
  "products",
  {
    id: integer("id").primaryKey({ autoIncrement: true }),
    name: text("name").notNull(),
    slug: text("slug").notNull().unique(),
    description: text("description"),
    basePrice: real("base_price").notNull().default(0),
    categoryId: integer("category_id").references(() => categories.id),
    sku: text("sku"),
    status: text("status", { enum: ["active", "draft"] })
      .notNull()
      .default("draft"),
    createdAt: text("created_at")
      .notNull()
      .default(sql`(datetime('now'))`),
    updatedAt: text("updated_at")
      .notNull()
      .default(sql`(datetime('now'))`),
  },
  (table) => ({
    slugIdx: index("idx_products_slug").on(table.slug),
    categoryIdx: index("idx_products_category_id").on(table.categoryId),
  }),
);

export const productImages = sqliteTable("product_images", {
  id: integer("id").primaryKey({ autoIncrement: true }),
  productId: integer("product_id")
    .notNull()
    .references(() => products.id),
  r2Key: text("r2_key").notNull(),
  altText: text("alt_text"),
  sortOrder: integer("sort_order").notNull().default(0),
  isPrimary: integer("is_primary").notNull().default(0),
});

export const customizationOptions = sqliteTable("customization_options", {
  id: integer("id").primaryKey({ autoIncrement: true }),
  productId: integer("product_id")
    .notNull()
    .references(() => products.id),
  optionType: text("option_type", {
    enum: ["text", "color", "font", "upload"],
  }).notNull(),
  label: text("label").notNull(),
  configJson: text("config_json"),
  required: integer("required").notNull().default(0),
  sortOrder: integer("sort_order").notNull().default(0),
});

export const customers = sqliteTable("customers", {
  id: integer("id").primaryKey({ autoIncrement: true }),
  email: text("email").notNull().unique(),
  name: text("name"),
  phone: text("phone"),
  createdAt: text("created_at")
    .notNull()
    .default(sql`(datetime('now'))`),
});

export const orders = sqliteTable(
  "orders",
  {
    id: integer("id").primaryKey({ autoIncrement: true }),
    customerId: integer("customer_id").references(() => customers.id),
    status: text("status").notNull().default("pending"),
    subtotal: real("subtotal").notNull().default(0),
    total: real("total").notNull().default(0),
    stripePaymentIntentId: text("stripe_payment_intent_id"),
    shippingAddressJson: text("shipping_address_json"),
    createdAt: text("created_at")
      .notNull()
      .default(sql`(datetime('now'))`),
  },
  (table) => ({
    customerIdx: index("idx_orders_customer_id").on(table.customerId),
  }),
);

export const orderItems = sqliteTable(
  "order_items",
  {
    id: integer("id").primaryKey({ autoIncrement: true }),
    orderId: integer("order_id")
      .notNull()
      .references(() => orders.id),
    productId: integer("product_id")
      .notNull()
      .references(() => products.id),
    quantity: integer("quantity").notNull().default(1),
    unitPrice: real("unit_price").notNull().default(0),
    customizationDataJson: text("customization_data_json"),
  },
  (table) => ({
    orderIdx: index("idx_order_items_order_id").on(table.orderId),
  }),
);
