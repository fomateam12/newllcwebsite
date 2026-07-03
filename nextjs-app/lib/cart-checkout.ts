/**
 * Checkout shipping-address schema (Zod) + US state list.
 * Shared by the /checkout form now and, later, by whatever server endpoint
 * receives the address once payments are switched on (lib/flags.ts).
 */
import { z } from "zod";

export const US_STATES = [
  ["AL", "Alabama"], ["AK", "Alaska"], ["AZ", "Arizona"], ["AR", "Arkansas"],
  ["CA", "California"], ["CO", "Colorado"], ["CT", "Connecticut"], ["DE", "Delaware"],
  ["DC", "District of Columbia"], ["FL", "Florida"], ["GA", "Georgia"], ["HI", "Hawaii"],
  ["ID", "Idaho"], ["IL", "Illinois"], ["IN", "Indiana"], ["IA", "Iowa"],
  ["KS", "Kansas"], ["KY", "Kentucky"], ["LA", "Louisiana"], ["ME", "Maine"],
  ["MD", "Maryland"], ["MA", "Massachusetts"], ["MI", "Michigan"], ["MN", "Minnesota"],
  ["MS", "Mississippi"], ["MO", "Missouri"], ["MT", "Montana"], ["NE", "Nebraska"],
  ["NV", "Nevada"], ["NH", "New Hampshire"], ["NJ", "New Jersey"], ["NM", "New Mexico"],
  ["NY", "New York"], ["NC", "North Carolina"], ["ND", "North Dakota"], ["OH", "Ohio"],
  ["OK", "Oklahoma"], ["OR", "Oregon"], ["PA", "Pennsylvania"], ["RI", "Rhode Island"],
  ["SC", "South Carolina"], ["SD", "South Dakota"], ["TN", "Tennessee"], ["TX", "Texas"],
  ["UT", "Utah"], ["VT", "Vermont"], ["VA", "Virginia"], ["WA", "Washington"],
  ["WV", "West Virginia"], ["WI", "Wisconsin"], ["WY", "Wyoming"],
] as const;

const stateCodes = US_STATES.map(([code]) => code) as unknown as [string, ...string[]];

export const shippingAddressSchema = z.object({
  name: z
    .string()
    .trim()
    .min(2, "Please enter the recipient's full name")
    .max(120, "Name is too long"),
  email: z.string().trim().email("Please enter a valid email address").max(254),
  phone: z
    .string()
    .trim()
    .regex(/^\+?[0-9() .-]{7,20}$/, "Please enter a valid phone number"),
  address1: z
    .string()
    .trim()
    .min(4, "Please enter a street address")
    .max(200, "Address is too long"),
  address2: z.string().trim().max(200, "Address is too long").optional().or(z.literal("")),
  city: z.string().trim().min(2, "Please enter a city").max(120, "City is too long"),
  state: z.enum(stateCodes, { message: "Please select a state" }),
  zip: z
    .string()
    .trim()
    .regex(/^\d{5}(-\d{4})?$/, "Please enter a valid ZIP code (e.g. 90210)"),
});

export type ShippingAddress = z.infer<typeof shippingAddressSchema>;

export type AddressFieldErrors = Partial<Record<keyof ShippingAddress, string>>;

/** Flatten a Zod error into one message per field for inline display. */
export function toFieldErrors(
  error: z.ZodError<ShippingAddress>,
): AddressFieldErrors {
  const out: AddressFieldErrors = {};
  for (const issue of error.issues) {
    const field = issue.path[0] as keyof ShippingAddress | undefined;
    if (field && !out[field]) out[field] = issue.message;
  }
  return out;
}
