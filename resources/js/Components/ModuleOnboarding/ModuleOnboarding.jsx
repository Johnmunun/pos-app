import React, { useState, useEffect, useCallback, useRef } from 'react';
import { createPortal } from 'react-dom';
import axios from 'axios';
import { ChevronLeft, ChevronRight, CheckCircle, Sparkles } from 'lucide-react';
import { Button } from '@/Components/ui/button';

const STATUS_COMPLETED = 1;

/**
 * Tutoriel guidé par module. Affiche des bulles explicatives sur les éléments ciblés.
 * Persiste les étapes complétées et permet "Je comprends" pour valider le module.
 * 100% responsive.
 */
export default function ModuleOnboarding({ moduleName }) {
  const [steps, setSteps] = useState([]);
  const [stepsCompleted, setStepsCompleted] = useState([]);
  const [status, setStatus] = useState(null);
  const [loading, setLoading] = useState(true);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [targetRect, setTargetRect] = useState(null);
  const [popoverPosition, setPopoverPosition] = useState({ top: 0, left: 0 });
  const overlayRef = useRef(null);
  const popoverRef = useRef(null);

  const fetchStatus = useCallback(async () => {
    try {
      const { data } = await axios.get(route('module-onboarding.status', { module: moduleName }));
      setStepsCompleted(data.steps_completed || []);
      setStatus(data.status);
      return data;
    } catch (e) {
      setStepsCompleted([]);
      setStatus(0);
      return { steps_completed: [], status: 0 };
    }
  }, [moduleName]);

  const fetchSteps = useCallback(async () => {
    try {
      const { data } = await axios.get(route('module-onboarding.steps', { module: moduleName }));
      setSteps(data.steps || []);
      return data.steps || [];
    } catch (e) {
      setSteps([]);
      return [];
    }
  }, [moduleName]);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      setLoading(true);
      const [statusData, stepsList] = await Promise.all([fetchStatus(), fetchSteps()]);
      if (cancelled) return;
      setLoading(false);
      if (statusData.status === STATUS_COMPLETED || !stepsList.length) return;
      const firstIncomplete = stepsList.findIndex((s) => !(statusData.steps_completed || []).includes(s.id));
      setCurrentIndex(firstIncomplete >= 0 ? firstIncomplete : 0);
    })();
    return () => { cancelled = true; };
  }, [moduleName, fetchStatus, fetchSteps]);

  const updateTargetPosition = useCallback(() => {
    const step = steps[currentIndex];
    if (!step) {
      setTargetRect(null);
      return;
    }
    const el = document.querySelector(step.target);
    if (!el) {
      setTargetRect(null);
      return;
    }
    const rect = el.getBoundingClientRect();
    if (rect.width === 0 && rect.height === 0) {
      setTargetRect(null);
      return;
    }
    setTargetRect({
      top: rect.top,
      left: rect.left,
      width: rect.width,
      height: rect.height,
    });
    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }, [steps, currentIndex]);

  useEffect(() => {
    if (!steps.length || status === STATUS_COMPLETED) return;
    updateTargetPosition();
    const timer = setTimeout(updateTargetPosition, 300);
    window.addEventListener('resize', updateTargetPosition);
    window.addEventListener('scroll', updateTargetPosition, true);
    return () => {
      clearTimeout(timer);
      window.removeEventListener('resize', updateTargetPosition);
      window.removeEventListener('scroll', updateTargetPosition, true);
    };
  }, [steps, currentIndex, status, updateTargetPosition]);

  useEffect(() => {
    const padding = 12;
    const viewportW = window.innerWidth;
    const viewportH = window.innerHeight;
    const maxPopWidth = Math.min(448, viewportW - 32);
    let top;
    let left;
    if (targetRect) {
      top = targetRect.top + targetRect.height + padding;
      left = targetRect.left + Math.max(0, (targetRect.width - maxPopWidth) / 2);
      if (top > viewportH / 2) {
        top = Math.max(16, targetRect.top - 280);
      }
      if (left < 16) left = 16;
      if (left + maxPopWidth > viewportW - 16) left = viewportW - maxPopWidth - 16;
    } else {
      top = Math.max(16, (viewportH - 280) / 2);
      left = (viewportW - maxPopWidth) / 2;
      if (left < 16) left = 16;
    }
    setPopoverPosition({ top, left });
  }, [targetRect]);

  const completeStep = useCallback(async (stepId) => {
    try {
      await axios.post(route('module-onboarding.steps.complete', { module: moduleName }), { step_id: stepId });
      setStepsCompleted((prev) => (prev.includes(stepId) ? prev : [...prev, stepId]));
    } catch (_) {}
  }, [moduleName]);

  const completeModule = useCallback(async () => {
    try {
      await axios.post(route('module-onboarding.complete', { module: moduleName }));
      setStatus(STATUS_COMPLETED);
    } catch (_) {}
  }, [moduleName]);

  const handleNext = useCallback(async () => {
    const step = steps[currentIndex];
    if (step) await completeStep(step.id);
    if (currentIndex < steps.length - 1) {
      setCurrentIndex((i) => i + 1);
    } else {
      await completeModule();
    }
  }, [currentIndex, steps, completeStep, completeModule]);

  const handlePrev = useCallback(() => {
    if (currentIndex > 0) setCurrentIndex((i) => i - 1);
  }, [currentIndex]);

  const handleJeComprends = useCallback(async () => {
    await completeModule();
  }, [completeModule]);

  if (loading || status === STATUS_COMPLETED || !steps.length) return null;

  const step = steps[currentIndex];
  if (!step) return null;

  const isLast = currentIndex === steps.length - 1;

  const progressPercent = steps.length ? ((currentIndex + 1) / steps.length) * 100 : 0;

  const overlay = (
    <div
      ref={overlayRef}
      className="fixed inset-0 z-[9998]"
      style={{ pointerEvents: 'none' }}
      aria-hidden="true"
    >
      {/* Backdrop semi-transparent (softer) */}
      <svg className="absolute inset-0 w-full h-full" style={{ pointerEvents: 'auto' }}>
        <defs>
          <mask id="module-onboarding-mask">
            <rect width="100%" height="100%" fill="white" />
            {targetRect && (
              <rect
                x={targetRect.left - 6}
                y={targetRect.top - 6}
                width={targetRect.width + 12}
                height={targetRect.height + 12}
                rx="12"
                fill="black"
              />
            )}
          </mask>
        </defs>
        <rect width="100%" height="100%" fill="rgba(15,23,42,0.6)" mask="url(#module-onboarding-mask)" />
      </svg>
      {/* Bordure highlight (glow) autour de l'élément */}
      {targetRect && (
        <>
          <div
            className="absolute rounded-xl ring-2 ring-amber-400/80 ring-offset-4 ring-offset-transparent bg-transparent pointer-events-none shadow-[0_0_24px_rgba(251,191,36,0.25)]"
            style={{
              top: targetRect.top - 6,
              left: targetRect.left - 6,
              width: targetRect.width + 12,
              height: targetRect.height + 12,
            }}
          />
        </>
      )}
    </div>
  );

  const popover = (
    <div
      ref={popoverRef}
      className="fixed z-[9999] w-[calc(100vw-2rem)] max-w-md pointer-events-auto overflow-hidden rounded-2xl border border-amber-200/50 dark:border-amber-500/30 bg-white dark:bg-gray-800 shadow-2xl shadow-amber-900/10 dark:shadow-black/40"
      style={{
        top: popoverPosition.top,
        left: popoverPosition.left,
        maxWidth: 'min(448px, calc(100vw - 2rem))',
      }}
      role="dialog"
      aria-labelledby="onboarding-title"
      aria-describedby="onboarding-content"
    >
      <div className="h-1 bg-gray-100 dark:bg-gray-700">
        <div
          className="h-full bg-gradient-to-r from-amber-500 to-amber-600 transition-all duration-300 ease-out"
          style={{ width: `${progressPercent}%` }}
        />
      </div>
      <div className="p-5">
        <div className="flex items-start gap-3 mb-3">
          <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400">
            <Sparkles className="h-5 w-5" />
          </div>
          <div className="flex-1 min-w-0">
            <h3 id="onboarding-title" className="text-base font-semibold text-gray-900 dark:text-white leading-tight">
              {step.title}
            </h3>
            <span className="text-xs text-amber-600 dark:text-amber-400 font-medium mt-0.5 block">
              Étape {currentIndex + 1} sur {steps.length}
            </span>
          </div>
        </div>
        <p id="onboarding-content" className="text-sm text-gray-600 dark:text-gray-300 leading-relaxed mb-5 pl-12">
          {step.content}
        </p>
        <div className="flex flex-wrap items-center gap-2 pl-12">
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={handlePrev}
            disabled={currentIndex === 0}
            className="gap-1.5 border-gray-300 dark:border-gray-600"
          >
            <ChevronLeft className="h-4 w-4" />
            <span className="hidden xs:inline">Précédent</span>
          </Button>
          {!isLast ? (
            <Button type="button" size="sm" onClick={handleNext} className="gap-1.5 bg-amber-600 hover:bg-amber-700 text-white shadow-sm">
              <span>Suivant</span>
              <ChevronRight className="h-4 w-4" />
            </Button>
          ) : (
            <Button
              type="button"
              size="sm"
              onClick={handleNext}
              className="gap-1.5 bg-amber-600 hover:bg-amber-700 text-white shadow-sm"
            >
              <CheckCircle className="h-4 w-4" />
              Terminer
            </Button>
          )}
          <Button
            type="button"
            variant="ghost"
            size="sm"
            onClick={handleJeComprends}
            className="ml-auto text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 text-xs"
          >
            Je comprends
          </Button>
        </div>
      </div>
    </div>
  );

  return (
    <>
      {typeof document !== 'undefined' && createPortal(overlay, document.body)}
      {typeof document !== 'undefined' && createPortal(popover, document.body)}
    </>
  );
}
