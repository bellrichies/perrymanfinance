import { useLocation } from 'react-router-dom';
import type { SeoMetadata } from '../insights/insightService';
import { useQuery } from '@tanstack/react-query';
import { publicService } from './publicService';

type JsonLd = Record<string, unknown> | Record<string, unknown>[];

export function Metadata({ title, seo, unavailable = false, jsonLd, ogType = 'website' }: { title: string; seo?: SeoMetadata | null; unavailable?: boolean; jsonLd?: JsonLd; ogType?: 'website' | 'article' }) {
  const { pathname } = useLocation();
  const page = useQuery({ queryKey: ['page', pathname.slice(1)], queryFn: ({ signal }) => publicService.page(pathname.slice(1), signal), enabled: !seo && ['investments', 'insights', 'faq', 'contact'].includes(pathname.slice(1)) });
  seo = seo ?? page.data?.data.seo;
  const origin = import.meta.env.VITE_SITE_URL;
  const canonical = seo?.canonical_url || (origin ? `${origin.replace(/\/$/, '')}${pathname}` : undefined);
  const resolvedOrigin = origin || (canonical ? new URL(canonical).origin : undefined);
  const baseJsonLd: Record<string, unknown>[] = canonical ? [{ '@context': 'https://schema.org', '@type': 'BreadcrumbList', itemListElement: [{ '@type': 'ListItem', position: 1, name: 'Home', item: resolvedOrigin }, { '@type': 'ListItem', position: 2, name: title, item: canonical }] }] : [];
  if (pathname === '/' && canonical) baseJsonLd.unshift({ '@context': 'https://schema.org', '@type': 'Organization', name: 'PerrymanFinance', url: canonical });
  const structured = [...baseJsonLd, ...normalizeJsonLd(jsonLd)].filter(Boolean);
  const rawImage = typeof seo?.open_graph?.image === 'string' ? seo.open_graph.image : undefined;
  const image = rawImage && canonical ? absoluteUrl(rawImage, canonical) : rawImage;
  const metaTitle = seo?.meta_title || `${title} | PerrymanFinance`;
  const metaDescription = seo?.meta_description || '';
  return <>{structured.length > 0 && <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structured.length === 1 ? structured[0] : structured).replace(/</g, '\\u003c') }} />}<title>{metaTitle}</title><meta name="description" content={metaDescription} /><meta name="robots" content={unavailable ? 'noindex,nofollow' : seo?.robots || 'noindex,follow'} />{canonical && <link rel="canonical" href={canonical} />}<meta property="og:title" content={String(seo?.open_graph?.title || metaTitle)} /><meta property="og:description" content={String(seo?.open_graph?.description || metaDescription)} /><meta property="og:type" content={ogType} />{canonical && <meta property="og:url" content={canonical} />}{image && <meta property="og:image" content={image} />}<meta name="twitter:card" content={image ? 'summary_large_image' : 'summary'} /><meta name="twitter:title" content={metaTitle} /><meta name="twitter:description" content={metaDescription} />{image && <meta name="twitter:image" content={image} />}</>;
}

function normalizeJsonLd(jsonLd?: JsonLd): Record<string, unknown>[] {
  if (!jsonLd) return [];
  return Array.isArray(jsonLd) ? jsonLd : [jsonLd];
}

function absoluteUrl(value: string, base: string): string {
  try {
    return new URL(value, base).toString();
  } catch {
    return value;
  }
}
