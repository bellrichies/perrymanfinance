import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { cmsService } from './cmsService';

export function usePages() { return useQuery({ queryKey: ['admin', 'pages'], queryFn: cmsService.pages }); }
export function useSavePage() { const client = useQueryClient(); return useMutation({ mutationFn: cmsService.savePage, onSuccess: () => client.invalidateQueries({ queryKey: ['admin', 'pages'] }) }); }
export function useLegal() { return useQuery({ queryKey: ['admin', 'legal'], queryFn: cmsService.legal }); }
export function useSettings() { return useQuery({ queryKey: ['admin', 'settings'], queryFn: cmsService.settings }); }
export function useMedia() { return useQuery({ queryKey: ['admin', 'media'], queryFn: cmsService.media }); }
