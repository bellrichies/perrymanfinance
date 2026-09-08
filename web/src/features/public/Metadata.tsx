import { useLocation } from 'react-router-dom';
import type { SeoMetadata } from '../insights/insightService';
import { useQuery } from '@tanstack/react-query';
import { publicService } from './publicService';

export function Metadata({ title, seo, unavailable = false }: { title: string; seo?: SeoMetadata | null; unavailable?: boolean }) {
  const { pathname } = useLocation();
  const page = useQuery({ queryKey: ['page', pathname.slice(1)], queryFn: ({ signal }) => publicService.page(pathname.slice(1), signal), enabled: !seo && ['investments', 'insights', 'faq', 'contact'].includes(pathname.slice(1)) });
  seo = seo ?? page.data?.data.seo;
  const origin = import.meta.env.VITE_SITE_URL;
  const canonical = seo?.canonical_url || (origin ? `${origin.replace(/\/$/, '')}${pathname}` : undefined);
  const structured = canonical ? { '@context': 'https://schema.org', '@type': pathname === '/' ? 'Organization' : 'BreadcrumbList', ...(pathname === '/' ? { name: 'PerrymanFinance', url: canonical } : { itemListElement: [{ '@type': 'ListItem', position: 1, name: 'Home', item: origin || new URL(canonical).origin }, { '@type': 'ListItem', position: 2, name: title, item: canonical }] }) } : null;
  const image = typeof seo?.open_graph?.image === 'string' ? seo.open_graph.image : undefined;
  return <>{structured && <script type="application/ld+json" dangerouslySetInnerHTML={{ __html: JSON.stringify(structured).replace(/</g, '\\u003c') }} />}<title>{seo?.meta_title || `${title} | PerrymanFinance`}</title><meta name="description" content={seo?.meta_description || ''} /><meta name="robots" content={unavailable ? 'noindex,nofollow' : seo?.robots || 'noindex,follow'} />{canonical && <link rel="canonical" href={canonical} />}<meta property="og:title" content={seo?.meta_title || title} /><meta property="og:description" content={seo?.meta_description || ''} /><meta property="og:type" content="website" />{canonical && <meta property="og:url" content={canonical} />}{image && <meta property="og:image" content={image} />}<meta name="twitter:card" content={image ? 'summary_large_image' : 'summary'} />{image && <meta name="twitter:image" content={image} />}</>;
}
