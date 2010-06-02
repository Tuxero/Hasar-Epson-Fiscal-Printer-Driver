VERSION 5.00
Object = "{648A5603-2C6E-101B-82B6-000000000014}#1.1#0"; "mscomm32.ocx"
Begin VB.Form Form1 
   Caption         =   "Form1"
   ClientHeight    =   3195
   ClientLeft      =   60
   ClientTop       =   345
   ClientWidth     =   4680
   LinkTopic       =   "Form1"
   ScaleHeight     =   3195
   ScaleWidth      =   4680
   StartUpPosition =   3  'Windows Default
   Begin VB.Timer Timer1 
      Enabled         =   0   'False
      Interval        =   100
      Left            =   2220
      Top             =   1920
   End
   Begin MSCommLib.MSComm MSComm1 
      Left            =   1440
      Top             =   1260
      _ExtentX        =   1005
      _ExtentY        =   1005
      _Version        =   393216
   End
End
Attribute VB_Name = "Form1"
Attribute VB_GlobalNameSpace = False
Attribute VB_Creatable = False
Attribute VB_PredeclaredId = True
Attribute VB_Exposed = False
Dim Contador_timer As Integer


Private Sub Form_Load()
entrada_salida = Split(Command, "_____")

ar = Split(entrada_salida(0), "___")

MSComm1.CommPort = ar(1)
MSComm1.Settings = "9600,N,8,1"
MSComm1.InputLen = 0


w = FreeFile
Open entrada_salida(0) For Input As w
    While Not EOF(w)
        Line Input #w, b
        MSComm1.PortOpen = True
        Contador_timer = 0
        Timer1.Enabled = True
        While Contador_timer < 3
            DoEvents
        Wend
        Timer1.Enabled = False
        MSComm1.Output = b
        
        nnn = 0
        buffer$ = ""
        Do
            DoEvents
            buffer$ = buffer$ & MSComm1.Input
            pos = InStr(1, buffer$, Chr(3))
            
            nnn = nnn + 1
        Loop Until (pos > 0 And buffer$ <> "") Or nnn = 100
        
        salida = salida & buffer$ & vbCrLf
        
        Contador_timer = 0
        Timer1.Enabled = True
        While Contador_timer < 3
            DoEvents
        Wend
        Timer1.Enabled = False
        MSComm1.PortOpen = False
    Wend
Close w

w = FreeFile
Open entrada_salida(1) For Output As w
    Print #w, salida
Close w

End
End Sub

Private Sub Timer1_Timer()
Contador_timer = Contador_timer + 1
End Sub
