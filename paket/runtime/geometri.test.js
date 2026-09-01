import { describe, expect, it } from 'vitest'
import { keLayar, normalisasiSudut, pantulkanDiTepi } from './geometri.js'

describe('keLayar', () => {
  it('memetakan origin panggung ke tengah kanvas', () => {
    expect(keLayar(0, 0, 480, 360)).toEqual([240, 180])
  })

  it('membalik sumbu y (atas di panggung = y piksel lebih kecil)', () => {
    expect(keLayar(10, 50, 480, 360)).toEqual([250, 130])
  })
})

describe('normalisasiSudut', () => {
  it('membiarkan sudut dalam rentang 0-360 apa adanya', () => {
    expect(normalisasiSudut(90)).toBe(90)
  })
  it('membungkus sudut negatif', () => {
    expect(normalisasiSudut(-90)).toBe(270)
  })
  it('membungkus sudut lebih dari 360', () => {
    expect(normalisasiSudut(450)).toBe(90)
  })
})

describe('pantulkanDiTepi', () => {
  const LEBAR = 480
  const TINGGI = 360

  it('tidak memantulkan sprite yang masih di dalam panggung', () => {
    const hasil = pantulkanDiTepi({ x: 0, y: 0, arah: 90 }, LEBAR, TINGGI)
    expect(hasil).toEqual({ x: 0, y: 0, arah: 90, kena: false })
  })

  it('memantulkan arah horizontal saat melewati tepi kanan', () => {
    const hasil = pantulkanDiTepi({ x: 300, y: 0, arah: 90 }, LEBAR, TINGGI)
    expect(hasil.kena).toBe(true)
    expect(hasil.x).toBe(LEBAR / 2 - 22)
    expect(hasil.arah).toBe(270) // -90 dinormalisasi jadi 270
  })

  it('memantulkan arah horizontal saat melewati tepi kiri', () => {
    const hasil = pantulkanDiTepi({ x: -300, y: 0, arah: 90 }, LEBAR, TINGGI)
    expect(hasil.kena).toBe(true)
    expect(hasil.x).toBe(-(LEBAR / 2 - 22))
    expect(hasil.arah).toBe(270)
  })

  it('memantulkan arah vertikal saat melewati tepi atas', () => {
    const hasil = pantulkanDiTepi({ x: 0, y: 200, arah: 0 }, LEBAR, TINGGI)
    expect(hasil.kena).toBe(true)
    expect(hasil.y).toBe(TINGGI / 2 - 22)
    expect(hasil.arah).toBe(180) // 180 - 0
  })

  it('memantulkan arah vertikal saat melewati tepi bawah', () => {
    const hasil = pantulkanDiTepi({ x: 0, y: -200, arah: 180 }, LEBAR, TINGGI)
    expect(hasil.kena).toBe(true)
    expect(hasil.y).toBe(-(TINGGI / 2 - 22))
    expect(hasil.arah).toBe(0) // 180 - 180
  })

  it('tidak mengubah posisi saat tepat di batas', () => {
    const batasX = LEBAR / 2 - 22
    const hasil = pantulkanDiTepi({ x: batasX, y: 0, arah: 45 }, LEBAR, TINGGI)
    expect(hasil.kena).toBe(false)
    expect(hasil.x).toBe(batasX)
    expect(hasil.arah).toBe(45)
  })
})
